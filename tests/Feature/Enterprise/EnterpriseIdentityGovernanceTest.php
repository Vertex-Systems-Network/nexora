<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\EnterpriseInvitation;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseSsoProvider;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Contracts\EnterpriseIdentityProviderContract;
use App\Nexora\Enterprise\Services\ImpersonationManager;
use App\Nexora\Enterprise\Services\InvitationManager;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use App\Nexora\Enterprise\Services\SsoProviderRegistry;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class EnterpriseIdentityGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_enforced_sso_blocks_member_password_login_but_preserves_super_admin_break_glass(): void
    {
        $organization = $this->defaultOrganization();
        $member = User::factory()->create([
            'email' => 'sso-member@example.test',
            'password' => Hash::make('member-secret'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $this->addMember($organization, $member, 'member');
        $this->createSsoProvider($organization, 'enforced', true);

        $this->post('/login', [
            'email' => $member->email,
            'password' => 'member-secret',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $superAdmin = User::factory()->create([
            'email' => 'break-glass@example.test',
            'password' => Hash::make('super-secret'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $superAdmin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'super-secret',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_sso_state_is_bound_to_the_originating_provider_and_callback_protocol_is_rechecked(): void
    {
        $organization = $this->defaultOrganization();
        $registry = app(SsoProviderRegistry::class);
        $registry->register($this->fakeIdentityAdapter('tests.oidc', 'oidc'));

        $first = $this->createSsoProvider($organization, 'first', false, 'tests.oidc', 'oidc');
        $second = $this->createSsoProvider($organization, 'second', false, 'tests.oidc', 'oidc');

        $start = $this->get('/sso/'.$organization->slug.'/'.$first->slug);
        $start->assertRedirect();
        parse_str((string) parse_url((string) $start->headers->get('Location'), PHP_URL_QUERY), $query);
        $state = (string) ($query['state'] ?? '');
        self::assertNotSame('', $state);

        $this->get('/sso/'.$organization->slug.'/'.$second->slug.'/callback?state='.urlencode($state))
            ->assertStatus(419);

        // A compatible adapter must also still match the provider protocol at callback time.
        $registry = app(SsoProviderRegistry::class);
        $provider = EnterpriseSsoProvider::query()->whereKey($first->id)->firstOrFail();
        $provider->forceFill(['protocol' => 'saml'])->saveQuietly();

        $this->get('/sso/'.$organization->slug.'/'.$first->slug.'/callback?state='.urlencode($state))
            ->assertStatus(419);
    }

    public function test_sso_public_configuration_rejects_secret_like_keys(): void
    {
        $organization = $this->defaultOrganization();

        $this->expectException(ValidationException::class);

        EnterpriseSsoProvider::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Unsafe provider',
            'slug' => 'unsafe-provider',
            'protocol' => 'oidc',
            'adapter_key' => 'tests.oidc',
            'enabled' => false,
            'enforce_for_members' => false,
            'configuration' => ['client_secret' => 'must-not-be-public'],
            'secret_payload' => ['client_secret' => 'encrypted-here'],
        ]);
    }

    public function test_scim_token_fails_closed_after_organization_is_suspended(): void
    {
        $organization = $this->defaultOrganization();
        $issued = app(ScimTokenManager::class)->issue($organization, 'Suspension test');
        $organization->update(['status' => 'suspended']);

        $this->withToken($issued['token'])
            ->getJson('/scim/v2/Users')
            ->assertUnauthorized();
    }

    public function test_scim_cannot_attach_an_existing_foreign_platform_identity(): void
    {
        $organization = $this->defaultOrganization();
        $issued = app(ScimTokenManager::class)->issue($organization, 'Isolation test');
        $foreign = User::factory()->create([
            'email' => 'foreign-identity@example.test',
            'status' => 'active',
        ]);

        $this->withToken($issued['token'])
            ->postJson('/scim/v2/Users', [
                'userName' => $foreign->email,
                'active' => true,
            ])
            ->assertStatus(409);

        $this->assertDatabaseMissing('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $foreign->id,
        ]);
    }

    public function test_scim_active_is_tenant_local_and_privileged_membership_is_not_demoted_or_deactivated(): void
    {
        $organization = $this->defaultOrganization();
        $issued = app(ScimTokenManager::class)->issue($organization, 'Lifecycle test');

        $created = $this->withToken($issued['token'])
            ->postJson('/scim/v2/Users', [
                'userName' => 'tenant-local@example.test',
                'active' => false,
                'name' => ['givenName' => 'Tenant', 'familyName' => 'Local'],
            ])
            ->assertCreated()
            ->assertJsonPath('active', false);

        $newUserId = (int) $created->json('id');
        $this->assertDatabaseHas('users', ['id' => $newUserId, 'status' => 'active']);
        $this->assertDatabaseHas('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $newUserId,
            'status' => 'suspended',
        ]);

        $owner = User::factory()->create([
            'email' => 'scim-owner@example.test',
            'status' => 'active',
        ]);
        $this->addMember($organization, $owner, 'owner');

        $this->withToken($issued['token'])
            ->postJson('/scim/v2/Users', [
                'userName' => $owner->email,
                'active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('roles.0.value', 'owner');

        $this->withToken($issued['token'])
            ->patchJson('/scim/v2/Users/'.$owner->id, [
                'Operations' => [[
                    'op' => 'replace',
                    'path' => 'active',
                    'value' => false,
                ]],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_new_invitation_supersedes_old_token_and_acceptance_preserves_privileged_role(): void
    {
        $organization = $this->defaultOrganization();
        $actor = $this->superAdmin();
        $user = User::factory()->create([
            'email' => 'invited@example.test',
            'status' => 'active',
        ]);
        $this->addMember($organization, $user, 'owner');
        $manager = app(InvitationManager::class);

        $old = $manager->create($organization, $user->email, 'viewer', $actor);
        $current = $manager->create($organization, $user->email, 'member', $actor);

        $this->assertDatabaseHas('nx_enterprise_invitations', [
            'id' => $old['invitation']->id,
            'status' => 'superseded',
        ]);

        try {
            $manager->accept($old['token'], $user);
            self::fail('A superseded invitation token must not be accepted.');
        } catch (ValidationException) {
            self::assertTrue(true);
        }

        $membership = $manager->accept($current['token'], $user);
        self::assertSame('owner', $membership->fresh()->role);
    }

    public function test_invitation_acceptance_selects_the_accepted_organization(): void
    {
        $organization = $this->createOrganization('Invited tenant', 'invited-tenant');
        $actor = $this->superAdmin();
        $user = User::factory()->create([
            'email' => 'tenant-select@example.test',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $created = app(InvitationManager::class)->create($organization, $user->email, 'member', $actor);

        $this->actingAs($user)
            ->get('/enterprise/invitations/'.$created['token'].'/accept')
            ->assertSessionHas('nexora.enterprise.organization_id', $organization->id);
    }

    public function test_nested_impersonation_is_rejected_before_identity_switch(): void
    {
        $organization = $this->defaultOrganization();
        $actor = $this->superAdmin();
        $target = User::factory()->create(['status' => 'active']);
        $this->addMember($organization, $target, 'member');

        $this->actingAs($actor)
            ->withSession([
                'nexora.enterprise.organization_id' => $organization->id,
                'nexora.enterprise.impersonation_id' => (string) Str::uuid(),
                'nexora.enterprise.impersonator_id' => $actor->id,
            ])
            ->post('/admin/enterprise/organizations/'.$organization->id.'/impersonate', [
                'target_user_id' => $target->id,
                'reason' => 'Nested impersonation must remain unavailable.',
            ])
            ->assertSessionHasErrors('target_user_id');

        $this->assertAuthenticatedAs($actor);
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function createOrganization(string $name, string $slug): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }

    private function addMember(EnterpriseOrganization $organization, User $user, string $role): EnterpriseOrganizationMember
    {
        return EnterpriseOrganizationMember::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        return $user;
    }

    private function createSsoProvider(
        EnterpriseOrganization $organization,
        string $slug,
        bool $enforced,
        string $adapter = 'tests.missing',
        string $protocol = 'oidc',
    ): EnterpriseSsoProvider {
        return EnterpriseSsoProvider::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'name' => ucfirst($slug).' SSO',
            'slug' => $slug,
            'protocol' => $protocol,
            'adapter_key' => $adapter,
            'enabled' => true,
            'enforce_for_members' => $enforced,
            'configuration' => [],
            'secret_payload' => [],
        ]);
    }

    private function fakeIdentityAdapter(string $key, string $protocol): EnterpriseIdentityProviderContract
    {
        return new class($key, $protocol) implements EnterpriseIdentityProviderContract {
            public function __construct(
                private readonly string $adapterKey,
                private readonly string $adapterProtocol,
            ) {}

            public function key(): string { return $this->adapterKey; }
            public function protocol(): string { return $this->adapterProtocol; }
            public function health(EnterpriseSsoProvider $provider): array { return ['ok' => true]; }
            public function redirectUrl(EnterpriseSsoProvider $provider, string $state): string
            {
                return 'https://identity.example.test/login?state='.urlencode($state);
            }
            public function resolveIdentity(EnterpriseSsoProvider $provider, Request $request): array
            {
                return ['email' => 'resolved@example.test'];
            }
        };
    }
}
