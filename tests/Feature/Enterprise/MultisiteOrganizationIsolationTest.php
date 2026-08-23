<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\EnterpriseDomain;
use App\Models\EnterpriseOrganization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\OrganizationManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MultisiteOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_current_tenant_permissions_cannot_be_reused_against_another_organization_route(): void
    {
        $admin = $this->enterpriseAdmin([
            'enterprise.view',
            'enterprise.members.manage',
            'enterprise.domains.manage',
        ]);
        $current = $this->organizationFor($admin, 'Current Organization', 'current-organization');
        $other = $this->organizationFor($admin, 'Other Organization', 'other-organization');

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $current->id])
            ->get('/admin/enterprise/organizations/'.$other->id)
            ->assertNotFound();

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $current->id])
            ->post('/admin/enterprise/organizations/'.$other->id.'/invitations', [
                'email' => 'must-not-invite@example.test',
                'role' => 'member',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('nx_enterprise_invitations', [
            'organization_id' => $other->id,
            'email' => 'must-not-invite@example.test',
        ]);
    }

    public function test_organization_switch_hides_inaccessible_tenants_and_preserves_current_session(): void
    {
        $admin = $this->enterpriseAdmin(['enterprise.view']);
        $current = $this->organizationFor($admin, 'Accessible Organization', 'accessible-organization');
        $inaccessible = EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Hidden Organization',
            'slug' => 'hidden-organization',
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $current->id])
            ->post('/admin/enterprise/switch', [
                'organization_id' => $inaccessible->id,
            ])
            ->assertNotFound()
            ->assertSessionHas('nexora.enterprise.organization_id', $current->id);
    }

    public function test_organization_admin_does_not_receive_platform_user_directory_or_direct_attach_access(): void
    {
        $admin = $this->enterpriseAdmin([
            'enterprise.view',
            'enterprise.members.manage',
        ]);
        $organization = $this->organizationFor($admin, 'Tenant Admin Organization', 'tenant-admin-organization');
        $outsider = User::factory()->create([
            'name' => 'Platform Outsider',
            'email' => 'platform-outsider@example.test',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->get('/admin/enterprise/organizations/'.$organization->id)
            ->assertOk()
            ->assertDontSee('platform-outsider@example.test');

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->post('/admin/enterprise/organizations/'.$organization->id.'/members', [
                'user_id' => $outsider->id,
                'role' => 'member',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_organization_admin_can_still_invite_by_email_without_platform_user_enumeration(): void
    {
        $admin = $this->enterpriseAdmin([
            'enterprise.view',
            'enterprise.members.manage',
        ]);
        $organization = $this->organizationFor($admin, 'Invitation Organization', 'invitation-organization');

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->post('/admin/enterprise/organizations/'.$organization->id.'/invitations', [
                'email' => 'invited-person@example.test',
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('nx_enterprise_invitations', [
            'organization_id' => $organization->id,
            'email' => 'invited-person@example.test',
            'role' => 'member',
            'status' => 'pending',
        ]);
    }

    public function test_impersonation_validation_is_scoped_to_active_organization_members(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $admin = $this->enterpriseAdmin([
            'enterprise.view',
            'enterprise.impersonate',
        ]);
        $organization = $this->organizationFor($admin, 'Impersonation Organization', 'impersonation-organization');
        $outsider = User::factory()->create([
            'email' => 'nonmember-target@example.test',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->from('/admin/enterprise/organizations/'.$organization->id)
            ->post('/admin/enterprise/organizations/'.$organization->id.'/impersonate', [
                'target_user_id' => $outsider->id,
                'reason' => 'Required for a controlled support investigation.',
            ])
            ->assertRedirect('/admin/enterprise/organizations/'.$organization->id)
            ->assertSessionHasErrors('target_user_id');

        $this->assertDatabaseMissing('nx_enterprise_impersonation_sessions', [
            'organization_id' => $organization->id,
            'target_user_id' => $outsider->id,
        ]);
    }

    public function test_nested_resource_from_another_organization_fails_before_mutation(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $admin = $this->enterpriseAdmin([
            'enterprise.view',
            'enterprise.domains.manage',
        ]);
        $current = $this->organizationFor($admin, 'Domain Current', 'domain-current');
        $other = $this->organizationFor($admin, 'Domain Other', 'domain-other');
        $domain = EnterpriseDomain::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $other->id,
            'host' => 'other-tenant.example.test',
            'status' => 'pending',
            'is_primary' => false,
            'verification_token_hash' => hash('sha256', 'test-token'),
        ]);

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $current->id])
            ->post('/admin/enterprise/organizations/'.$other->id.'/domains/'.$domain->id.'/verify')
            ->assertNotFound();

        $this->assertDatabaseHas('nx_enterprise_domains', [
            'id' => $domain->id,
            'organization_id' => $other->id,
            'status' => 'pending',
        ]);
    }

    private function enterpriseAdmin(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $role = Role::query()->create([
            'name' => 'Multisite QA '.Str::random(8),
            'slug' => 'multisite-qa-'.Str::lower(Str::random(10)),
            'description' => 'N1.16 acceptance role.',
            'is_system' => false,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('slug', array_values(array_unique(array_merge(['admin.access'], $permissions))))
            ->pluck('id');
        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function organizationFor(User $actor, string $name, string $slug): EnterpriseOrganization
    {
        return app(OrganizationManager::class)->create([
            'name' => $name,
            'slug' => $slug,
            'timezone' => 'UTC',
            'locale' => 'en',
        ], $actor);
    }
}
