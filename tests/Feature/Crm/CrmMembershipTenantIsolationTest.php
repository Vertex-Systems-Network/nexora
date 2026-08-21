<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Models\CommerceCustomer;
use App\Models\CrmCommerceLink;
use App\Models\CrmContact;
use App\Models\CrmCustomFieldDefinition;
use App\Models\CrmPipeline;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\MembershipAccessPolicy;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Crm\Contracts\CrmCommerceLinkContract;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class CrmMembershipTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_crm_commerce_links_are_tenant_scoped_and_cross_tenant_links_fail_closed(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other CRM tenant', 'other-crm-tenant');
        $context = app(TenantContext::class);

        $context->set($primary);
        $customer = CommerceCustomer::query()->create([
            'name' => 'Primary customer',
            'email' => 'primary-customer@example.test',
        ]);
        $contact = CrmContact::query()->create([
            'first_name' => 'Primary',
            'display_name' => 'Primary Contact',
            'email' => 'primary-contact@example.test',
            'lifecycle_stage' => 'customer',
        ]);
        $link = app(CrmCommerceLinkContract::class)->link($customer, $contact);

        self::assertSame($primary->id, $link->tenant_id);
        self::assertSame(1, CrmCommerceLink::query()->count());

        $context->set($other);
        self::assertSame(0, CrmCommerceLink::query()->count());
        $otherContact = CrmContact::query()->create([
            'first_name' => 'Other',
            'display_name' => 'Other Contact',
            'email' => 'other-contact@example.test',
            'lifecycle_stage' => 'customer',
        ]);

        $context->set($primary);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CRM contact must belong to the current organization.');
        app(CrmCommerceLinkContract::class)->link($customer, $otherContact);
    }

    public function test_crm_and_membership_identity_keys_can_repeat_across_tenants(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other identity tenant', 'other-identity-tenant');
        $context = app(TenantContext::class);

        $context->set($primary);
        $this->createSharedTenantIdentitySet();

        $context->set($other);
        $this->createSharedTenantIdentitySet();

        self::assertSame(2, CrmPipeline::query()->withoutGlobalScope('nexora_tenant')->where('slug', 'shared-sales')->count());
        self::assertSame(2, CrmCustomFieldDefinition::query()->withoutGlobalScope('nexora_tenant')->where('entity_type', 'contact')->where('key', 'region_code')->count());
        self::assertSame(2, MembershipPlan::query()->withoutGlobalScope('nexora_tenant')->where('slug', 'shared-premium')->count());
        self::assertSame(2, MembershipAccessPolicy::query()->withoutGlobalScope('nexora_tenant')->where('resource_type', 'document')->where('resource_id', 'shared-resource')->count());
    }

    public function test_membership_manager_rejects_cross_tenant_commerce_customer(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other membership tenant', 'other-membership-tenant');
        $context = app(TenantContext::class);

        $context->set($primary);
        $plan = MembershipPlan::query()->create([
            'name' => 'Primary Premium',
            'slug' => 'primary-premium',
            'status' => 'active',
            'metadata' => [],
        ]);

        $context->set($other);
        $otherCustomer = CommerceCustomer::query()->create([
            'name' => 'Other customer',
            'email' => 'other-membership-customer@example.test',
        ]);

        $context->set($primary);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commerce customer must belong to the membership organization.');
        app(MembershipManagerContract::class)->grant($plan, null, [
            'commerce_customer_id' => $otherCustomer->id,
            'status' => 'active',
        ]);
    }

    public function test_membership_member_picker_excludes_users_from_other_tenants(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other member tenant', 'other-member-tenant');
        $admin = User::factory()->create([
            'name' => 'Portal Admin',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        $primaryUser = User::factory()->create(['name' => 'Primary Member', 'email' => 'primary-member@example.test']);
        $otherUser = User::factory()->create(['name' => 'Other Member', 'email' => 'other-member@example.test']);

        $this->addMember($primary, $admin, 'owner');
        $this->addMember($primary, $primaryUser);
        $this->addMember($other, $otherUser);
        app(TenantContext::class)->set($primary);

        $response = $this->actingAs($admin)->get('/admin/membership/members');

        $response->assertOk();
        $response->assertSee('primary-member@example.test');
        $response->assertDontSee('other-member@example.test');
    }

    private function createSharedTenantIdentitySet(): void
    {
        CrmPipeline::query()->create([
            'name' => 'Shared Sales',
            'slug' => 'shared-sales',
            'is_default' => false,
            'active' => true,
        ]);
        CrmCustomFieldDefinition::query()->create([
            'entity_type' => 'contact',
            'key' => 'region_code',
            'label' => 'Region code',
            'field_type' => 'text',
            'options' => [],
            'required' => false,
            'active' => true,
            'position' => 10,
        ]);
        MembershipPlan::query()->create([
            'name' => 'Shared Premium',
            'slug' => 'shared-premium',
            'status' => 'active',
            'metadata' => [],
        ]);
        MembershipAccessPolicy::query()->create([
            'name' => 'Shared resource policy',
            'resource_type' => 'document',
            'resource_id' => 'shared-resource',
            'evaluation' => 'any',
            'required_plan_ids' => [],
            'required_entitlements' => [],
            'unauthenticated_action' => 'deny',
            'active' => true,
            'metadata' => [],
        ]);
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function createOrganization(string $name, string $slug): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }

    private function addMember(EnterpriseOrganization $organization, User $user, string $role = 'member'): void
    {
        EnterpriseOrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
