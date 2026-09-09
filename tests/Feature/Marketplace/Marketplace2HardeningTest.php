<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseRole;
use App\Models\MarketplaceCatalogItem;
use App\Models\MarketplaceSource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class Marketplace2HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_generation_null_catalog_is_hidden_until_fresh_sync(): void
    {
        $admin = $this->superAdmin();
        [$source] = $this->catalogFixture(null, null);

        $this->actingAs($admin)
            ->get('/admin/extensions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('catalog', 0)
                ->where('summary.catalog', 0));

        $this->assertNull($source->fresh()?->catalog_generation);
    }

    public function test_matching_catalog_generation_is_visible(): void
    {
        $admin = $this->superAdmin();
        $generation = (string) Str::uuid();
        $this->catalogFixture($generation, $generation);

        $this->actingAs($admin)
            ->get('/admin/extensions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('catalog', 1)
                ->where('summary.catalog', 1));
    }

    public function test_generation_mismatch_blocks_staging_before_download(): void
    {
        $admin = $this->superAdmin();
        [, $item] = $this->catalogFixture((string) Str::uuid(), (string) Str::uuid());

        $this->actingAs($admin)
            ->post('/admin/extensions/marketplace/catalog/'.$item->id.'/stage')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('nx_quarantine_packages', 0);
    }

    public function test_current_tenant_role_can_deny_globally_granted_marketplace_stage_permission(): void
    {
        $operator = $this->operatorWithPermissions('extensions.view', 'extensions.install');
        $organization = EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Restricted Marketplace Organization',
            'slug' => 'restricted-marketplace-'.Str::lower(Str::random(8)),
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
        EnterpriseRole::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Marketplace Viewer',
            'slug' => 'marketplace-viewer',
            'permissions' => ['admin.access', 'extensions.view'],
            'is_system' => false,
        ]);
        EnterpriseOrganizationMember::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'user_id' => $operator->id,
            'role' => 'marketplace-viewer',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $generation = (string) Str::uuid();
        [, $item] = $this->catalogFixture($generation, $generation);

        $this->actingAs($operator)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->post('/admin/extensions/marketplace/catalog/'.$item->id.'/stage')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('nx_quarantine_packages', 0);
    }

    /** @return array{0:MarketplaceSource,1:MarketplaceCatalogItem} */
    private function catalogFixture(?string $sourceGeneration, ?string $itemGeneration): array
    {
        $suffix = Str::lower(Str::random(8));
        $source = MarketplaceSource::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Marketplace 2 '.$suffix,
            'base_url' => 'https://'.$suffix.'.marketplace.example.test',
            'status' => 'active',
            'trusted_publishers_only' => false,
            'catalog_generation' => $sourceGeneration,
            'last_synced_at' => $sourceGeneration === null ? null : now(),
        ]);
        $item = MarketplaceCatalogItem::query()->create([
            'id' => (string) Str::uuid(),
            'source_id' => $source->id,
            'package_identifier' => 'example/marketplace-'.$suffix,
            'name' => 'Marketplace 2 Package',
            'type' => 'extension',
            'latest_version' => '2.0.0',
            'artifact_url' => 'https://packages.example.test/marketplace-'.$suffix.'.zip',
            'artifact_sha256' => str_repeat('a', 64),
            'metadata' => [],
            'sync_generation' => $itemGeneration,
            'synced_at' => $itemGeneration === null ? null : now(),
        ]);

        return [$source, $item];
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        return $user;
    }

    private function operatorWithPermissions(string ...$permissionSlugs): User
    {
        $role = Role::query()->create([
            'name' => 'Marketplace 2 Operator '.Str::random(6),
            'slug' => 'marketplace2-operator-'.Str::lower(Str::random(8)),
            'description' => 'Marketplace 2 tenant-authorization regression role.',
            'is_system' => false,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('slug', array_values(array_unique(array_merge(['admin.access'], $permissionSlugs))))
            ->pluck('id');
        $role->permissions()->sync($permissionIds);

        $operator = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $operator->roles()->attach($role->id);

        return $operator;
    }
}
