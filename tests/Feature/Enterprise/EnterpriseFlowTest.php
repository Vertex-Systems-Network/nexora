<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\CrmPipeline;
use App\Models\Document;
use App\Models\EnterpriseOrganization;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class EnterpriseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_super_admin_can_create_and_switch_organization(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach(
            Role::query()->where('slug', 'super-admin')->value('id'),
        );

        $this->actingAs($admin)
            ->post('/admin/enterprise/organizations', [
                'name' => 'Acme Holdings',
                'slug' => 'acme',
                'timezone' => 'UTC',
                'locale' => 'en',
            ])
            ->assertRedirect();

        $organization = EnterpriseOrganization::query()
            ->where('slug', 'acme')
            ->firstOrFail();

        $this->assertDatabaseHas('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'owner',
        ]);

        $this->actingAs($admin)
            ->post('/admin/enterprise/switch', [
                'organization_id' => $organization->id,
            ])
            ->assertRedirect();
    }

    public function test_document_queries_are_isolated_by_tenant_context(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other', 'other');
        $context = app(TenantContext::class);

        $context->set($primary);
        Document::query()->create($this->documentPayload('Primary document', 'primary-document'));

        $context->set($other);
        Document::query()->create($this->documentPayload('Other document', 'other-document'));

        self::assertSame(1, Document::query()->count());
        self::assertSame('Other document', Document::query()->firstOrFail()->title);

        $context->set($primary);
        self::assertSame(1, Document::query()->count());
        self::assertSame('Primary document', Document::query()->firstOrFail()->title);
    }

    public function test_scim_token_provisions_user_into_only_its_organization(): void
    {
        $organization = $this->defaultOrganization();
        $issued = app(ScimTokenManager::class)->issue($organization, 'Test SCIM');

        $this->withToken($issued['token'])
            ->postJson('/scim/v2/Users', [
                'userName' => 'scim@example.com',
                'active' => true,
                'name' => [
                    'givenName' => 'SCIM',
                    'familyName' => 'User',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('userName', 'scim@example.com');

        $user = User::query()->where('email', 'scim@example.com')->firstOrFail();

        $this->assertDatabaseHas('nx_enterprise_organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_core_seeder_discards_stale_tenant_context_after_schema_replacement(): void
    {
        $defaultOrganization = $this->defaultOrganization();
        $staleOrganization = $this->createOrganization('Old tenant', 'old-tenant');
        $staleTenantId = $staleOrganization->id;
        $context = app(TenantContext::class);

        $context->set($staleOrganization);
        $staleOrganization->delete();

        $this->seed(NexoraCoreSeeder::class);

        self::assertFalse($context->active());
        $this->assertDatabaseMissing('nx_enterprise_organizations', [
            'id' => $staleTenantId,
        ]);
        $this->assertDatabaseMissing('nx_crm_pipelines', [
            'tenant_id' => $staleTenantId,
        ]);
        $this->assertDatabaseHas('nx_crm_pipelines', [
            'slug' => 'sales',
            'tenant_id' => $defaultOrganization->id,
        ]);
        $this->assertDatabaseHas('nx_helpdesk_sla_policies', [
            'name' => 'Standard',
            'tenant_id' => $defaultOrganization->id,
        ]);
        $this->assertDatabaseHas('nx_newsletter_lists', [
            'slug' => 'general-updates',
            'tenant_id' => $defaultOrganization->id,
        ]);
    }

    public function test_tenant_scoped_write_rejects_an_active_context_for_a_deleted_organization(): void
    {
        $staleOrganization = $this->createOrganization('Deleted tenant', 'deleted-tenant');
        app(TenantContext::class)->set($staleOrganization);
        $staleOrganization->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The active Nexora tenant context points to an organization that no longer exists.',
        );

        CrmPipeline::query()->create([
            'slug' => 'must-not-persist',
            'name' => 'Must not persist',
            'is_default' => false,
            'active' => true,
        ]);
    }

    public function test_scoped_tenant_context_restores_previous_context_after_an_exception(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Scoped tenant', 'scoped-tenant');
        $context = app(TenantContext::class);
        $context->set($primary);

        try {
            $context->runWith($other, static function (): void {
                throw new RuntimeException('expected test exception');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('expected test exception', $exception->getMessage());
        }

        self::assertSame($primary->id, $context->id());
    }


    public function test_tenant_execution_scope_uses_fresh_organization_and_restores_previous_context(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Queue tenant', 'queue-tenant');
        $context = app(TenantContext::class);
        $scope = app(TenantExecutionScope::class);
        $context->set($primary);

        $observedTenantId = $scope->runRequired(
            $other->id,
            'test tenant execution',
            fn (): ?string => $context->id(),
        );

        self::assertSame($other->id, $observedTenantId);
        self::assertSame($primary->id, $context->id());
    }

    public function test_tenant_execution_scope_rejects_missing_or_deleted_tenants(): void
    {
        $scope = app(TenantExecutionScope::class);

        try {
            $scope->runRequired(null, 'missing tenant test', static fn () => null);
            self::fail('Missing tenant identifiers must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('has no tenant identifier', $exception->getMessage());
        }

        $deleted = $this->createOrganization('Deleted queue tenant', 'deleted-queue-tenant');
        $deletedId = $deleted->id;
        $deleted->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("tenant {$deletedId} no longer exists or is not active");

        $scope->runRequired($deletedId, 'deleted tenant test', static fn () => null);
    }


    public function test_tenant_execution_scope_rejects_a_suspended_tenant(): void
    {
        $organization = $this->createOrganization('Suspended queue tenant', 'suspended-queue-tenant');
        $organization->update(['status' => 'suspended']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no longer exists or is not active');

        app(TenantExecutionScope::class)->runRequired(
            $organization->id,
            'suspended tenant test',
            static fn () => null,
        );
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()
            ->where('is_default', true)
            ->firstOrFail();
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

    /** @return array<string, mixed> */
    private function documentPayload(string $title, string $slug): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => 'document',
            'status' => 'draft',
            'title' => $title,
            'slug' => $slug,
            'content' => [
                'version' => 1,
                'blocks' => [],
            ],
            'schema_version' => 1,
        ];
    }
}
