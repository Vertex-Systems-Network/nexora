<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Models\ApiAccessToken;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseSsoProvider;
use App\Models\User;
use App\Nexora\Api\Services\ApiAbilityRegistry;
use App\Nexora\Api\Services\ApiTokenManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/** MOBILE-AUTH-API-100 — see .ai/security/threat-models/MOBILE-AUTH-API-100.md. */
final class MobileAuthTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        RateLimiter::clear('nexora-api-auth:ip:127.0.0.1');
    }

    public function test_valid_credentials_issue_a_self_scoped_token_that_reads_me(): void
    {
        $user = $this->member('ayesha@example.test');

        $response = $this->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.abilities', ['profile.read'])
            ->assertJsonPath('data.organization.id', $this->defaultOrganization()->id);

        $token = (string) $response->json('data.token');
        self::assertStringStartsWith('nxapi_', $token);
        $this->assertDatabaseMissing('nx_api_access_tokens', ['token_hash' => $token]);

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'ayesha@example.test')
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.token.name', 'Mobile · iPhone 16');
    }

    public function test_me_payload_is_an_allow_list_without_secrets(): void
    {
        $user = $this->member('safe@example.test');
        $token = $this->issueToken($user);

        $data = $this->withToken($token)->getJson('/api/v1/me')->assertOk()->json('data');

        self::assertSame(['id', 'name', 'email', 'email_verified', 'organization', 'token'], array_keys($data));
        $raw = json_encode($data, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('password', $raw);
        self::assertStringNotContainsString('remember_token', $raw);
        self::assertStringNotContainsString('token_hash', $raw);
    }

    public function test_wrong_password_and_unknown_email_fail_identically(): void
    {
        $user = $this->member('known@example.test');

        $wrong = $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'not-it', 'device_name' => 'Pixel'])
            ->assertStatus(422)->json();
        $unknown = $this->postJson('/api/v1/auth/token', ['email' => 'nobody@example.test', 'password' => 'not-it', 'device_name' => 'Pixel'])
            ->assertStatus(422)->json();

        self::assertSame($wrong, $unknown);
        self::assertSame('invalid_credentials', $wrong['error']['code']);
        self::assertSame(0, ApiAccessToken::withoutGlobalScope('nexora_tenant')->count());
    }

    public function test_suspended_account_is_refused_after_password_check(): void
    {
        $user = $this->member('suspended@example.test', ['status' => 'suspended']);

        $this->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'account_unavailable');
        self::assertSame(0, ApiAccessToken::withoutGlobalScope('nexora_tenant')->count());
    }

    public function test_unverified_email_is_refused(): void
    {
        $user = $this->member('unverified@example.test', ['email_verified_at' => null]);

        $this->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'email_unverified');
    }

    public function test_sso_enforced_organization_blocks_password_exchange(): void
    {
        $organization = $this->defaultOrganization();
        $user = $this->member('sso@example.test');
        EnterpriseSsoProvider::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Corp SSO',
            'slug' => 'corp-sso',
            'protocol' => 'oidc',
            'adapter_key' => 'tests.oidc',
            'enabled' => true,
            'enforce_for_members' => true,
            'configuration' => [],
            'secret_payload' => [],
        ]);

        $this->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'sso_required');
        self::assertSame(0, ApiAccessToken::withoutGlobalScope('nexora_tenant')->count());
    }

    public function test_foreign_organization_cannot_be_requested(): void
    {
        $user = $this->member('member@example.test');
        $foreign = EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(), 'name' => 'Foreign', 'slug' => 'foreign-org',
            'status' => 'active', 'is_default' => false, 'timezone' => 'UTC', 'locale' => 'en',
        ]);

        $this->postJson('/api/v1/auth/token', $this->credentials($user) + ['organization' => $foreign->slug])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'organization_unavailable');
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $user = $this->member('target@example.test');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'guess-'.$i, 'device_name' => 'x'])
                ->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_account_limit_holds_across_rotating_ips(): void
    {
        $user = $this->member('distributed@example.test');
        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.'.($i + 1)])
                ->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'guess-'.$i, 'device_name' => 'x'])
                ->assertStatus(422);
        }

        // Correct password from yet another IP is still refused: the per-account bucket ignores IP.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->postJson('/api/v1/auth/token', $this->credentials($user))
            ->assertStatus(429);
    }

    public function test_blocked_states_count_toward_the_limit(): void
    {
        $user = $this->member('blocked-loop@example.test', ['status' => 'suspended']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', $this->credentials($user))->assertForbidden();
        }

        $this->postJson('/api/v1/auth/token', $this->credentials($user))->assertStatus(429);
    }

    public function test_token_cap_never_revokes_admin_issued_tokens_sharing_the_prefix(): void
    {
        $user = $this->member('admin-named@example.test');
        $adminIssued = app(ApiTokenManager::class)->issue(
            $this->defaultOrganization(),
            $user,
            AuthTokenController::TOKEN_NAME_PREFIX.'integration',
            [ApiAbilityRegistry::DOCUMENTS_READ],
            30,
        );
        for ($i = 0; $i < AuthTokenController::MAX_ACTIVE_TOKENS + 1; $i++) {
            $this->travel(1)->seconds();
            $this->issueToken($user, 'Device '.$i);
        }

        self::assertNull($adminIssued['record']->fresh()->revoked_at);
    }

    public function test_exchanged_token_cannot_use_other_abilities(): void
    {
        $token = $this->issueToken($this->member('scope@example.test'));

        $this->withToken($token)->getJson('/api/v1/documents')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'insufficient_scope');
    }

    public function test_logout_revokes_only_the_presenting_token(): void
    {
        $user = $this->member('two-devices@example.test');
        $phone = $this->issueToken($user, 'Phone');
        $tablet = $this->issueToken($user, 'Tablet');

        $this->withToken($phone)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($phone)->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken($tablet)->getJson('/api/v1/me')->assertOk();
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'invalid_token');
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_active_mobile_tokens_are_capped_per_user(): void
    {
        $user = $this->member('many@example.test');
        for ($i = 0; $i < AuthTokenController::MAX_ACTIVE_TOKENS + 2; $i++) {
            $this->travel(1)->seconds();
            $this->issueToken($user, 'Device '.$i);
        }

        $active = ApiAccessToken::withoutGlobalScope('nexora_tenant')
            ->where('user_id', $user->id)->whereNull('revoked_at')->pluck('name')->all();

        self::assertCount(AuthTokenController::MAX_ACTIVE_TOKENS, $active);
        self::assertNotContains('Mobile · Device 0', $active);
        self::assertContains('Mobile · Device 11', $active);
    }

    /** @param array<string,mixed> $attributes */
    private function member(string $email, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + [
            'email' => $email,
            'password' => 'correct-horse-battery',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        EnterpriseOrganizationMember::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->defaultOrganization()->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }

    /** @return array{email:string,password:string,device_name:string} */
    private function credentials(User $user, string $device = 'iPhone 16'): array
    {
        return ['email' => $user->email, 'password' => 'correct-horse-battery', 'device_name' => $device];
    }

    private function issueToken(User $user, string $device = 'iPhone 16'): string
    {
        RateLimiter::clear('nexora-api-auth:ip:127.0.0.1');

        return (string) $this->postJson('/api/v1/auth/token', $this->credentials($user, $device))
            ->assertCreated()
            ->json('data.token');
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }
}
