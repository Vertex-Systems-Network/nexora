<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Models\MarketplaceCatalogItem;
use App\Models\MarketplaceSource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MarketplaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_pausing_source_hides_catalog_and_blocks_staging(): void
    {
        $admin = $this->administrator();
        [$source, $item] = $this->catalogFixture('active');

        $this->actingAs($admin)
            ->patch('/admin/extensions/marketplace/sources/'.$source->id.'/status', ['status' => 'paused'])
            ->assertSessionHasNoErrors();

        self::assertSame('paused', $source->fresh()?->status);

        $this->actingAs($admin)
            ->get('/admin/extensions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('catalog', 0)
                ->where('summary.catalog', 0));

        $this->actingAs($admin)
            ->post('/admin/extensions/marketplace/items/'.$item->id.'/stage')
            ->assertSessionHas('error');
    }

    public function test_resuming_source_requires_fresh_sync_before_catalog_or_staging(): void
    {
        $admin = $this->administrator();
        [$source, $item] = $this->catalogFixture('paused');

        $this->actingAs($admin)
            ->patch('/admin/extensions/marketplace/sources/'.$source->id.'/status', ['status' => 'active'])
            ->assertSessionHasNoErrors();

        $fresh = $source->fresh();
        self::assertSame('active', $fresh?->status);
        self::assertNull($fresh?->last_synced_at);

        $this->actingAs($admin)
            ->get('/admin/extensions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('catalog', 0)
                ->where('summary.catalog', 0));

        $this->actingAs($admin)
            ->post('/admin/extensions/marketplace/items/'.$item->id.'/stage')
            ->assertSessionHas('error');
    }

    public function test_staging_permission_matches_the_owning_package_engine(): void
    {
        $extensionOperator = $this->operatorWithPermissions('extensions.install');
        [, $themeItem] = $this->catalogFixture('active', 'theme');

        $this->actingAs($extensionOperator)
            ->post('/admin/extensions/marketplace/catalog/'.$themeItem->id.'/stage')
            ->assertSessionHas('error');
        $this->assertDatabaseCount('nx_quarantine_packages', 0);

        $themeOperator = $this->operatorWithPermissions('themes.install');
        [, $extensionItem] = $this->catalogFixture('active', 'extension', 'second');

        $this->actingAs($themeOperator)
            ->post('/admin/extensions/marketplace/catalog/'.$extensionItem->id.'/stage')
            ->assertSessionHas('error');
        $this->assertDatabaseCount('nx_quarantine_packages', 0);
    }

    public function test_source_must_be_paused_before_removal_and_catalog_cache_cascades(): void
    {
        $admin = $this->administrator();
        [$source, $item] = $this->catalogFixture('active');

        $this->actingAs($admin)
            ->delete('/admin/extensions/marketplace/sources/'.$source->id)
            ->assertSessionHas('error');
        $this->assertDatabaseHas('nx_marketplace_sources', ['id' => $source->id]);

        $this->actingAs($admin)
            ->patch('/admin/extensions/marketplace/sources/'.$source->id.'/status', ['status' => 'paused'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->delete('/admin/extensions/marketplace/sources/'.$source->id)
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('nx_marketplace_sources', ['id' => $source->id]);
        $this->assertDatabaseMissing('nx_marketplace_catalog_items', ['id' => $item->id]);
    }

    public function test_marketplace_status_only_accepts_known_lifecycle_states(): void
    {
        $admin = $this->administrator();
        [$source] = $this->catalogFixture('active');

        $this->actingAs($admin)
            ->patch('/admin/extensions/marketplace/sources/'.$source->id.'/status', ['status' => 'deleted'])
            ->assertSessionHasErrors(['status']);

        self::assertSame('active', $source->fresh()?->status);
    }

    /** @return array{0:MarketplaceSource,1:MarketplaceCatalogItem} */
    private function catalogFixture(string $status, string $type = 'extension', string $suffix = 'primary'): array
    {
        $source = MarketplaceSource::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Marketplace '.$suffix,
            'base_url' => 'https://'.$suffix.'.marketplace.example.test',
            'status' => $status,
            'trusted_publishers_only' => false,
            'last_synced_at' => now(),
        ]);
        $item = MarketplaceCatalogItem::query()->create([
            'id' => (string) Str::uuid(),
            'source_id' => $source->id,
            'package_identifier' => 'example/test-'.$type.'-'.$suffix,
            'name' => 'Test '.Str::headline($type),
            'type' => $type,
            'latest_version' => '1.0.0',
            'artifact_url' => 'https://packages.example.test/test-'.$type.'-'.$suffix.'.zip',
            'artifact_sha256' => str_repeat('a', 64),
            'metadata' => [],
            'synced_at' => now(),
        ]);

        return [$source, $item];
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }

    private function operatorWithPermissions(string ...$permissionSlugs): User
    {
        $role = Role::query()->create([
            'name' => 'Marketplace Operator '.Str::random(6),
            'slug' => 'marketplace-operator-'.Str::lower(Str::random(8)),
            'description' => 'Marketplace permission-boundary acceptance role.',
            'is_system' => false,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('slug', array_merge(['admin.access'], $permissionSlugs))
            ->pluck('id');
        $role->permissions()->sync($permissionIds);

        $operator = User::factory()->create(['email_verified_at' => now()]);
        $operator->roles()->attach($role->id);

        return $operator;
    }
}
