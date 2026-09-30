<?php

declare(strict_types=1);

namespace Tests\Feature\Discovery;

use App\Models\Document;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\MembershipAccessPolicy;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SearchProductIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        app(TenantContext::class)->set($this->defaultOrganization());
    }

    public function test_public_search_excludes_membership_protected_published_documents(): void
    {
        $public = $this->createPublishedDocument(
            'Public Search Boundary',
            'public-search-boundary',
            'search boundary phrase available to everyone',
        );
        $protected = $this->createPublishedDocument(
            'Protected Search Boundary',
            'protected-search-boundary',
            'search boundary phrase restricted to members',
        );

        MembershipAccessPolicy::query()->create([
            'name' => 'Protect search fixture',
            'resource_type' => 'document',
            'resource_id' => (string) $protected->id,
            'evaluation' => 'any',
            'required_plan_ids' => [],
            'required_entitlements' => [],
            'unauthenticated_action' => 'deny',
            'active' => true,
            'metadata' => [],
        ]);

        $response = $this->get('/search?q=search+boundary+phrase');

        $response->assertOk();
        $response->assertSee($public->title);
        $response->assertDontSee($protected->title);
    }

    public function test_admin_global_search_does_not_disclose_users_from_another_tenant(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other search tenant', 'other-search-tenant');
        $admin = User::factory()->create([
            'name' => 'Search Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $primaryUser = User::factory()->create([
            'name' => 'Needle Primary Member',
            'email' => 'needle-primary@example.test',
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Needle Other Member',
            'email' => 'needle-other@example.test',
            'status' => 'active',
        ]);

        $this->addMember($primary, $admin, 'owner');
        $this->addMember($primary, $primaryUser);
        $this->addMember($other, $otherUser);
        app(TenantContext::class)->set($primary);

        // Admin JSON requests are intentionally fenced to the currently deployed
        // generation. Exercise the real client protocol instead of bypassing the
        // runtime guard; a missing/stale generation must remain a 409 in production.
        $generation = app(RuntimeDeploymentIdentity::class)->generation();
        $response = $this->actingAs($admin)
            ->withHeader('X-Nexora-Deployment-Generation', $generation)
            ->getJson('/admin/search?q=Needle');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Needle Primary Member']);
        $response->assertJsonMissing(['title' => 'Needle Other Member']);
        $response->assertJsonMissing(['subtitle' => 'needle-other@example.test']);
    }

    private function createPublishedDocument(string $title, string $slug, string $text): Document
    {
        return Document::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => 'article',
            'status' => 'published',
            'workflow_status' => 'published',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $text,
            'content' => [
                'version' => 1,
                'blocks' => [[
                    'id' => 'paragraph_0001',
                    'type' => 'paragraph',
                    'version' => 1,
                    'data' => ['text' => $text],
                    'children' => [],
                ]],
            ],
            'metadata' => [],
            'schema_version' => 1,
            'lock_version' => 1,
            'published_at' => now(),
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
