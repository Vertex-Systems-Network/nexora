<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\Passkey;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Security\Passkeys\PasskeyManager;
use App\Nexora\Security\TwoFactor\TotpAuthenticator;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\SoftwareAuthenticator;
use Tests\TestCase;

/** SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: registration and passwordless sign-in against a real software authenticator. */
final class PasskeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        // Admin JSON calls carry the deployment generation, as the admin fetch fence does in the browser.
        $this->withHeader('X-Nexora-Deployment-Generation', app(RuntimeDeploymentIdentity::class)->generation());
    }

    public function test_registration_requires_the_current_password(): void
    {
        $user = $this->admin('reg@example.test');

        $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => 'wrong'])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->assertSame(0, $user->passkeys()->count());
    }

    public function test_passkey_can_be_registered_and_used_to_sign_in(): void
    {
        $user = $this->admin('passkey@example.test');
        $authenticator = $this->registerPasskey($user);

        $this->assertDatabaseHas('nx_passkeys', ['user_id' => $user->id, 'name' => 'Laptop', 'credential_id' => SoftwareAuthenticator::b64u($authenticator->credentialId)]);
        $this->assertStringNotContainsString('credential_record', json_encode($user->passkeys()->first()->toArray()));

        auth()->logout();
        $this->flushSession();
        $this->assertGuest();

        $response = $this->postJson('/passkeys/login', ['credential' => $this->assertion($authenticator, $user)]);

        $response->assertOk()->assertJson(['redirect' => route('admin.dashboard')]);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->passkeys()->first()->last_used_at);
    }

    public function test_a_login_challenge_cannot_be_answered_twice(): void
    {
        $user = $this->admin('replay@example.test');
        $authenticator = $this->registerPasskey($user);
        auth()->logout();

        $options = $this->postJson('/passkeys/login/options')->assertOk()->json('publicKey');
        $credential = $authenticator->get($options, app(PasskeyManager::class)->userHandle($user));

        $this->postJson('/passkeys/login', ['credential' => $credential])->assertOk();
        auth()->logout();
        $this->postJson('/passkeys/login', ['credential' => $credential])->assertStatus(422)->assertJsonValidationErrors('passkey');
        $this->assertGuest();
    }

    public function test_an_unknown_credential_is_rejected(): void
    {
        $user = $this->admin('unknown@example.test');
        $stranger = new SoftwareAuthenticator;

        $this->postJson('/passkeys/login', ['credential' => $this->assertion($stranger, $user)])
            ->assertStatus(422)->assertJsonValidationErrors('passkey');
        $this->assertGuest();
    }

    public function test_a_forged_signature_for_a_registered_credential_is_rejected(): void
    {
        $user = $this->admin('forged@example.test');
        $authenticator = $this->registerPasskey($user);
        auth()->logout();
        // Same credential id, different private key: only the registered public key may sign.
        $impostor = new SoftwareAuthenticator(credentialId: $authenticator->credentialId);

        $this->postJson('/passkeys/login', ['credential' => $this->assertion($impostor, $user)])
            ->assertStatus(422)->assertJsonValidationErrors('passkey');
        $this->assertGuest();
    }

    public function test_assertions_without_user_verification_or_from_another_origin_are_rejected(): void
    {
        $user = $this->admin('uv@example.test');
        $authenticator = $this->registerPasskey($user);
        auth()->logout();
        $handle = app(PasskeyManager::class)->userHandle($user);

        // User present (0x01) but not verified: not a second factor, must not sign in.
        $options = $this->postJson('/passkeys/login/options')->json('publicKey');
        $this->postJson('/passkeys/login', ['credential' => $authenticator->get($options, $handle, 0x01)])->assertStatus(422);

        // Phishing origin: the browser reports the attacker's origin in clientDataJSON.
        $options = $this->postJson('/passkeys/login/options')->json('publicKey');
        $this->postJson('/passkeys/login', ['credential' => $authenticator->get($options, $handle, 0x05, 'https://login.attacker.test')])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_a_passkey_cannot_sign_in_as_another_user(): void
    {
        $owner = $this->admin('owner@example.test');
        $victim = $this->admin('victim@example.test');
        $authenticator = $this->registerPasskey($owner);
        auth()->logout();

        $options = $this->postJson('/passkeys/login/options')->json('publicKey');
        $credential = $authenticator->get($options, app(PasskeyManager::class)->userHandle($victim));

        $this->postJson('/passkeys/login', ['credential' => $credential])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_sign_in_with_a_passkey(): void
    {
        $user = $this->admin('inactive@example.test');
        $authenticator = $this->registerPasskey($user);
        auth()->logout();
        $user->forceFill(['status' => 'suspended'])->save();

        $this->postJson('/passkeys/login', ['credential' => $this->assertion($authenticator, $user)])
            ->assertStatus(422)->assertJsonValidationErrors('passkey');
        $this->assertGuest();
    }

    public function test_registration_cannot_reuse_a_ceremony_or_target_another_user(): void
    {
        $user = $this->admin('ceremony@example.test');
        $authenticator = new SoftwareAuthenticator;
        $options = $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD])->json('publicKey');
        $credential = $authenticator->create($options);

        $this->actingAs($user)->post('/admin/profile/passkeys', ['name' => 'Laptop', 'credential' => $credential])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/admin/profile/passkeys', ['name' => 'Again', 'credential' => $credential])->assertSessionHasErrors('passkey');

        $this->assertSame(1, $user->passkeys()->count());
    }

    public function test_removing_a_passkey_requires_the_password_and_ownership(): void
    {
        $owner = $this->admin('remove@example.test');
        $other = $this->admin('other@example.test');
        $this->registerPasskey($owner);
        $passkey = $owner->passkeys()->firstOrFail();

        $this->actingAs($owner)->delete('/admin/profile/passkeys/'.$passkey->id, ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->actingAs($other)->delete('/admin/profile/passkeys/'.$passkey->id, ['current_password' => self::PASSWORD])->assertNotFound();
        $this->assertModelExists($passkey);

        $this->actingAs($owner)->delete('/admin/profile/passkeys/'.$passkey->id, ['current_password' => self::PASSWORD])->assertSessionHasNoErrors();
        $this->assertModelMissing($passkey);
    }

    public function test_adding_a_passkey_to_an_mfa_account_requires_a_current_code(): void
    {
        $user = $this->admin('mfa-add@example.test');
        $twoFactor = app(TwoFactorManager::class);
        $totp = app(TotpAuthenticator::class);
        $twoFactor->begin($user);
        $twoFactor->confirm($user, $totp->codeAt((string) $user->fresh()->two_factor_secret, $totp->currentStep() - 1));
        $user = $user->fresh();

        // Hijacked session + password, no second factor: refused.
        $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD, 'code' => '000000'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $code = $totp->codeAt((string) $user->two_factor_secret, $totp->currentStep());
        $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD, 'code' => $code])
            ->assertOk()->assertJsonStructure(['publicKey' => ['challenge']]);
    }

    public function test_an_expired_login_ceremony_is_rejected(): void
    {
        $user = $this->admin('expired@example.test');
        $authenticator = $this->registerPasskey($user);
        auth()->logout();

        $credential = $this->assertion($authenticator, $user);
        $this->travel(PasskeyManager::TTL_SECONDS + 1)->seconds();

        $this->postJson('/passkeys/login', ['credential' => $credential])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_relying_party_follows_app_url_behind_a_tls_proxy(): void
    {
        // The app sees http://localhost (proxy terminated TLS) but users browse https://login.example.test.
        config(['app.url' => 'https://login.example.test']);
        $user = $this->admin('proxy@example.test');
        $authenticator = new SoftwareAuthenticator('https://login.example.test', 'login.example.test');

        $options = $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD])->assertOk()->json('publicKey');
        $this->assertSame('login.example.test', $options['rp']['id']);
        $this->actingAs($user)->post('/admin/profile/passkeys', ['name' => 'Proxy', 'credential' => $authenticator->create($options)])->assertSessionHasNoErrors();
        auth()->logout();

        $this->postJson('/passkeys/login', ['credential' => $this->assertion($authenticator, $user)])->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_handle_is_opaque(): void
    {
        $user = $this->admin('opaque@example.test');
        $handle = app(PasskeyManager::class)->userHandle($user);

        $this->assertSame(32, strlen($handle));
        $this->assertStringNotContainsString((string) $user->id, $handle);
        $this->assertStringNotContainsString($user->email, $handle);
    }

    private function registerPasskey(User $user): SoftwareAuthenticator
    {
        $authenticator = new SoftwareAuthenticator;
        $options = $this->actingAs($user)->postJson('/admin/profile/passkeys/options', ['current_password' => self::PASSWORD])
            ->assertOk()->json('publicKey');

        $this->actingAs($user)->post('/admin/profile/passkeys', ['name' => 'Laptop', 'credential' => $authenticator->create($options)])
            ->assertSessionHasNoErrors();
        $this->assertInstanceOf(Passkey::class, $user->passkeys()->first());

        return $authenticator;
    }

    private function assertion(SoftwareAuthenticator $authenticator, User $user): string
    {
        $options = $this->postJson('/passkeys/login/options')->assertOk()->json('publicKey');

        return $authenticator->get($options, app(PasskeyManager::class)->userHandle($user));
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
}
