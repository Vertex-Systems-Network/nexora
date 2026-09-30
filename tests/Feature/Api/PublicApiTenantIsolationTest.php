<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ApiAccessToken;
use App\Models\Document;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Api\Services\ApiAbilityRegistry;
use App\Nexora\Api\Services\ApiTokenManager;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PublicApiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_plaintext_token_is_never_persisted_and_reads_only_current_tenant_documents(): void
    {
        $organization = $this->defaultOrganization();
        $actor = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->addMember($organization, $actor, 'member');
        $document = $this->createDocumentFor($organization, 'Tenant API document');

        $issued = app(ApiTokenManager::class)->issue(
            $organization,
            $actor,
            'Acceptance token',
            [ApiAbilityRegistry::DOCUMENTS_READ],
            30,
        );

        self::assertFalse(Schema::hasColumn('nx_api_access_tokens', 'token'));
        self::assertSame(hash('sha256', $issued['token']), $issued['record']->fresh()->token_hash);
        $this->assertDatabaseMissing('nx_api_access_tokens', ['token_hash' => $issued['token']]);

        $this->withToken($issued['token'])
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonPath('api_version', 'v1')
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonPath('pagination.per_page', 25);
    }

    public function test_guessed_document_id_from_another_tenant_is_not_resolved(): void
    {
        $organization = $this->defaultOrganization();
        $other = $this->createOrganization('Other API tenant', 'other-api-tenant');
        $actor = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->addMember($organization, $actor, 'member');
        $otherDocument = $this->createDocumentFor($other, 'Foreign tenant document');
        $issued = app(ApiTokenManager::class)->issue(
            $organization,
            $actor,
            'Isolation token',
            [ApiAbilityRegistry::DOCUMENTS_READ],
            30,
        );

        $this->withToken($issued['token'])
            ->getJson('/api/v1/documents/'.$otherDocument->id)
            ->assertNotFound();

        $response = $this->withToken($issued['token'])->getJson('/api/v1/documents')->assertOk();
        self::assertNotContains($otherDocument->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_expired_revoked_and_stale_membership_tokens_fail_closed(): void
    {
        $organization = $this->defaultOrganization();
        $actor = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $membership = $this->addMember($organization, $actor, 'member');
        $manager = app(ApiTokenManager::class);

        $expired = $manager->issue($organization, $actor, 'Expired token', [ApiAbilityRegistry::DOCUMENTS_READ], 1);
        $expired['record']->forceFill(['expires_at' => now()->subSecond()])->saveQuietly();
        $this->withToken($expired['token'])->getJson('/api/v1/documents')->assertUnauthorized();

        $revoked = $manager->issue($organization, $actor, 'Revoked token', [ApiAbilityRegistry::DOCUMENTS_READ], 1);
        $manager->revoke($revoked['record'], $actor);
        $this->withToken($revoked['token'])->getJson('/api/v1/documents')->assertUnauthorized();

        $stale = $manager->issue($organization, $actor, 'Stale member token', [ApiAbilityRegistry::DOCUMENTS_READ], 1);
        $membership->forceFill(['status' => 'suspended'])->saveQuietly();
        $this->withToken($stale['token'])->getJson('/api/v1/documents')->assertUnauthorized();
    }

    public function test_missing_document_scope_is_forbidden_and_pagination_is_capped(): void
    {
        $organization = $this->defaultOrganization();
        $actor = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->addMember($organization, $actor, 'member');
        $manager = app(ApiTokenManager::class);
        $issued = $manager->issue($organization, $actor, 'Scope token', [ApiAbilityRegistry::DOCUMENTS_READ], 30);

        $issued['record']->forceFill(['abilities' => []])->saveQuietly();
        $this->withToken($issued['token'])
            ->getJson('/api/v1/documents')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'insufficient_scope');

        $issued['record']->forceFill(['abilities' => [ApiAbilityRegistry::DOCUMENTS_READ]])->saveQuietly();
        $this->withToken($issued['token'])
            ->getJson('/api/v1/documents?per_page=999')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 100);
    }

    public function test_admin_cannot_revoke_token_from_another_tenant_by_guessing_uuid(): void
    {
        $organization = $this->defaultOrganization();
        $other = $this->createOrganization('Other revoke tenant', 'other-revoke-tenant');
        $admin = $this->superAdmin();
        $otherActor = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->addMember($other, $otherActor, 'member');
        $issued = app(ApiTokenManager::class)->issue(
            $other,
            $otherActor,
            'Foreign revoke token',
            [ApiAbilityRegistry::DOCUMENTS_READ],
            30,
        );

        $this->actingAs($admin)
            ->withSession(['nexora.enterprise.organization_id' => $organization->id])
            ->delete('/admin/developer/api-tokens/'.$issued['record']->id)
            ->assertNotFound();

        self::assertNull(ApiAccessToken::withoutGlobalScope('nexora_tenant')->findOrFail($issued['record']->id)->revoked_at);
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

    private function createDocumentFor(EnterpriseOrganization $organization, string $title): Document
    {
        return app(TenantContext::class)->runWith($organization, static fn (): Document => Document::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
        ]));
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        return $user;
    }
}
