<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Models\ApiAccessToken;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Security\TwoFactor\TotpAuthenticator;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/** SECURITY-BASELINE-200 / SYS-MFA-TOTP — see .ai/security/threat-models/SECURITY-BASELINE-200-MFA-TOTP.md. */
final class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        RateLimiter::clear('nexora-api-auth:ip:127.0.0.1');
    }

    public function test_enrolment_requires_password_and_confirmation_before_enforcement(): void
    {
        $user = $this->admin('enrol@example.test');

        $this->actingAs($user)->post('/admin/profile/two-factor', ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        self::assertNull($user->fresh()->two_factor_secret);

        $this->actingAs($user)->post('/admin/profile/two-factor', ['current_password' => self::PASSWORD])->assertSessionHasNoErrors();
        $pending = $user->fresh();
        self::assertNotNull($pending->two_factor_secret);
        self::assertNull($pending->two_factor_confirmed_at);
        self::assertFalse(app(TwoFactorManager::class)->enabled($pending), 'Unconfirmed enrolment must never gate login.');

        $this->actingAs($user)->post('/admin/profile/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');

        $response = $this->actingAs($user)->post('/admin/profile/two-factor/confirm', ['code' => $this->currentCode($pending)]);
        $response->assertSessionHasNoErrors()->assertSessionHas('two_factor_recovery_codes');
        self::assertCount(TwoFactorManager::RECOVERY_CODE_COUNT, session('two_factor_recovery_codes'));
        self::assertTrue(app(TwoFactorManager::class)->enabled($user->fresh()));
    }

    public function test_secrets_are_encrypted_at_rest_and_never_serialized(): void
    {
        $user = $this->enrolledUser('rest@example.test');
        $raw = \DB::table('users')->where('id', $user->id)->first();

        self::assertNotSame($user->two_factor_secret, $raw->two_factor_secret);
        self::assertStringNotContainsString($user->two_factor_secret, (string) $raw->two_factor_secret);
        self::assertArrayNotHasKey('two_factor_secret', $user->toArray());
        self::assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
    }

    public function test_login_without_two_factor_is_unchanged(): void
    {
        $user = $this->admin('plain@example.test');

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_alone_does_not_authenticate_a_two_factor_user(): void
    {
        $user = $this->enrolledUser('mfa@example.test');

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD, 'remember' => true])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertCookieMissing(\Auth::guard('web')->getRecallerName());
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_valid_code_completes_login_and_replay_is_rejected(): void
    {
        $user = $this->enrolledUser('replay@example.test');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $code = $this->currentCode($user);

        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_wrong_codes_clear_the_pending_challenge(): void
    {
        // Isolate the per-challenge attempt cap from the shared auth: route throttle (which also stops this at 5/min).
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = $this->enrolledUser('brute@example.test');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);

        for ($i = 1; $i < TwoFactorChallengeController::MAX_ATTEMPTS; $i++) {
            $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertRedirect(route('login'));
        $this->assertNull(session(TwoFactorChallengeController::SESSION_KEY));

        $this->post('/two-factor-challenge', ['code' => $this->currentCode($user)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_expired_challenge_requires_signing_in_again(): void
    {
        $user = $this->enrolledUser('expired@example.test');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->travel(TwoFactorChallengeController::TTL_MINUTES + 1)->minutes();

        $this->post('/two-factor-challenge', ['code' => $this->currentCode($user)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_recovery_code_works_exactly_once(): void
    {
        $user = $this->enrolledUser('recovery@example.test', $codes);
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->post('/two-factor-challenge', ['code' => strtoupper($codes[0])])->assertRedirect(route('admin.dashboard'));
        self::assertSame(TwoFactorManager::RECOVERY_CODE_COUNT - 1, app(TwoFactorManager::class)->remainingRecoveryCodes($user->fresh()));

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->post('/two-factor-challenge', ['code' => $codes[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_disabling_requires_the_current_password(): void
    {
        $user = $this->enrolledUser('disable@example.test');

        $this->actingAs($user)->delete('/admin/profile/two-factor', ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        self::assertTrue(app(TwoFactorManager::class)->enabled($user->fresh()));

        $this->actingAs($user)->delete('/admin/profile/two-factor', ['current_password' => self::PASSWORD])->assertSessionHasNoErrors();
        self::assertFalse(app(TwoFactorManager::class)->enabled($user->fresh()));
    }

    public function test_api_token_exchange_requires_the_second_factor(): void
    {
        $user = $this->enrolledUser('api@example.test');
        $base = ['email' => $user->email, 'password' => self::PASSWORD, 'device_name' => 'iPhone'];

        $this->postJson('/api/v1/auth/token', $base)->assertStatus(422)->assertJsonPath('error.code', 'two_factor_required');
        $this->postJson('/api/v1/auth/token', $base + ['code' => '000000'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_credentials');
        self::assertSame(0, ApiAccessToken::withoutGlobalScope('nexora_tenant')->count());

        $this->postJson('/api/v1/auth/token', $base + ['code' => $this->currentCode($user)])->assertCreated();
        self::assertSame(1, ApiAccessToken::withoutGlobalScope('nexora_tenant')->count());
    }

    private function admin(string $email): User
    {
        $user = User::factory()->create(['email' => $email, 'password' => self::PASSWORD, 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        EnterpriseOrganizationMember::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => EnterpriseOrganization::query()->where('is_default', true)->value('id'),
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user->fresh();
    }

    /** @param-out list<string> $codes */
    private function enrolledUser(string $email, ?array &$codes = null): User
    {
        $user = $this->admin($email);
        $manager = app(TwoFactorManager::class);
        $manager->begin($user);
        $totp = app(TotpAuthenticator::class);
        // Confirm with the previous step so the current step stays available to the test.
        $previous = $totp->codeAt((string) $user->two_factor_secret, $totp->currentStep() - 1);
        $codes = $manager->confirm($user, $previous);
        self::assertNotNull($codes);

        return $user->fresh();
    }

    private function currentCode(User $user): string
    {
        $totp = app(TotpAuthenticator::class);

        return $totp->codeAt((string) $user->fresh()->two_factor_secret, $totp->currentStep());
    }
}
