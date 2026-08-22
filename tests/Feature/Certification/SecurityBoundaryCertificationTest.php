<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Models\Document;
use App\Models\CrmContact;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseRole;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Validation\TenantExists;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class SecurityBoundaryCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'Password!123', 'status' => 'suspended']);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post('/login', ['email' => $user->email, 'password' => 'Password!123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unknown_password_reset_email_gets_same_public_response_shape(): void
    {
        $this->post('/forgot-password', ['email' => 'does-not-exist@nexora.test'])
            ->assertSessionHas('status')
            ->assertSessionDoesntHaveErrors('email');
    }

    public function test_enterprise_role_can_restrict_admin_entry_even_when_platform_role_allows_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $org = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $role = EnterpriseRole::query()->where('organization_id', $org->id)->where('slug', 'viewer')->firstOrFail();
        $role->update(['permissions' => ['documents.view']]);
        EnterpriseOrganizationMember::query()->updateOrCreate(
            ['organization_id' => $org->id, 'user_id' => $user->id],
            ['id' => (string) Str::uuid(), 'role' => 'viewer', 'status' => 'active', 'joined_at' => now()],
        );

        $this->actingAs($user)->withSession(['nexora.enterprise.organization_id' => $org->id])->get('/admin')->assertForbidden();
    }

    public function test_admin_cannot_use_route_model_binding_to_cross_tenants(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        $primary = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $other = EnterpriseOrganization::query()->create(['name' => 'Other Tenant', 'slug' => 'other-tenant', 'status' => 'active', 'is_default' => false, 'timezone' => 'UTC', 'locale' => 'en']);
        $document = Document::withoutGlobalScope('nexora_tenant')->create([
            'tenant_id' => $other->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'document',
            'status' => 'draft',
            'title' => 'Other tenant secret',
            'slug' => 'other-tenant-secret',
            'content' => ['version' => 1, 'blocks' => []],
            'schema_version' => 1,
        ]);

        $this->actingAs($admin)->withSession(['nexora.enterprise.organization_id' => $primary->id])
            ->get('/admin/documents/'.$document->id.'/edit')->assertNotFound();
    }


    public function test_tenant_exists_rule_rejects_foreign_id_from_another_tenant(): void
    {
        $primary = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $other = EnterpriseOrganization::query()->create(['name' => 'Validation Tenant', 'slug' => 'validation-tenant', 'status' => 'active', 'is_default' => false, 'timezone' => 'UTC', 'locale' => 'en']);
        $contact = CrmContact::withoutGlobalScope('nexora_tenant')->create([
            'tenant_id' => $other->id,
            'first_name' => 'Foreign',
            'display_name' => 'Foreign Contact',
            'metadata' => [],
        ]);
        app(TenantContext::class)->set($primary);

        $validator = Validator::make(['contact_id' => $contact->id], [
            'contact_id' => [new TenantExists('nx_crm_contacts')],
        ]);

        self::assertTrue($validator->fails());
    }

    public function test_scim_suspension_is_organization_local_not_global_account_suspension(): void
    {
        $orgA = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
        $orgB = EnterpriseOrganization::query()->create(['name' => 'Tenant B', 'slug' => 'tenant-b', 'status' => 'active', 'is_default' => false, 'timezone' => 'UTC', 'locale' => 'en']);
        $user = User::factory()->create(['status' => 'active']);
        foreach ([$orgA, $orgB] as $org) {
            EnterpriseOrganizationMember::query()->create(['id' => (string) Str::uuid(), 'organization_id' => $org->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'active', 'joined_at' => now()]);
        }
        $issued = app(ScimTokenManager::class)->issue($orgA, 'RC6 SCIM');

        $this->withToken($issued['token'])->patchJson('/scim/v2/Users/'.$user->id, [
            'Operations' => [['op' => 'Replace', 'path' => 'active', 'value' => false]],
        ])->assertOk();

        self::assertSame('active', $user->fresh()->status);
        self::assertSame('suspended', EnterpriseOrganizationMember::query()->where('organization_id', $orgA->id)->where('user_id', $user->id)->value('status'));
        self::assertSame('active', EnterpriseOrganizationMember::query()->where('organization_id', $orgB->id)->where('user_id', $user->id)->value('status'));
    }
}
