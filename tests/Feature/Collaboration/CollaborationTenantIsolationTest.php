<?php

declare(strict_types=1);

namespace Tests\Feature\Collaboration;

use App\Models\AdminNotification;
use App\Models\Document;
use App\Models\DocumentReviewComment;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use App\Nexora\Enterprise\Validation\TenantMemberExists;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CollaborationTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_document_collaborator_directory_and_validation_exclude_cross_tenant_users(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other collaboration tenant', 'other-collaboration-tenant');
        $primaryUser = User::factory()->create([
            'name' => 'Primary Collaborator',
            'email' => 'primary-collaborator@example.test',
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Other Collaborator',
            'email' => 'other-collaborator@example.test',
            'status' => 'active',
        ]);

        $this->addMember($primary, $primaryUser);
        $this->addMember($other, $otherUser);
        app(TenantContext::class)->set($primary);

        $memberIds = app(TenantMemberDirectory::class)->activeUsers()->pluck('id')->all();
        self::assertContains($primaryUser->id, $memberIds);
        self::assertNotContains($otherUser->id, $memberIds);

        $allowed = Validator::make(
            ['assigned_to' => $primaryUser->id, 'reviewer_id' => $primaryUser->id],
            [
                'assigned_to' => ['nullable', 'integer', new TenantMemberExists()],
                'reviewer_id' => ['nullable', 'integer', new TenantMemberExists()],
            ],
        );
        self::assertFalse($allowed->fails());

        $blocked = Validator::make(
            ['assigned_to' => $otherUser->id, 'reviewer_id' => $otherUser->id],
            [
                'assigned_to' => ['nullable', 'integer', new TenantMemberExists()],
                'reviewer_id' => ['nullable', 'integer', new TenantMemberExists()],
            ],
        );
        self::assertTrue($blocked->fails());
    }

    public function test_review_comments_and_admin_notifications_are_isolated_by_tenant(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other collaboration scope', 'other-collaboration-scope');
        $user = User::factory()->create([
            'name' => 'Multi Tenant Reviewer',
            'email' => 'multi-tenant-reviewer@example.test',
            'status' => 'active',
        ]);
        $this->addMember($primary, $user);
        $this->addMember($other, $user);
        $context = app(TenantContext::class);

        $context->set($primary);
        $primaryDocument = $this->createDocument('Primary collaboration document');
        $primaryComment = DocumentReviewComment::query()->create([
            'document_id' => $primaryDocument->id,
            'user_id' => $user->id,
            'body' => 'Primary tenant review comment',
            'status' => 'open',
        ]);
        $primaryNotification = AdminNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'collaboration.review',
            'title' => 'Primary review requested',
            'message' => 'Primary tenant notification',
        ]);

        self::assertSame($primary->id, $primaryComment->tenant_id);
        self::assertSame($primary->id, $primaryNotification->tenant_id);

        $context->set($other);
        $otherDocument = $this->createDocument('Other collaboration document');
        $otherComment = DocumentReviewComment::query()->create([
            'document_id' => $otherDocument->id,
            'user_id' => $user->id,
            'body' => 'Other tenant review comment',
            'status' => 'open',
        ]);
        $otherNotification = AdminNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'collaboration.review',
            'title' => 'Other review requested',
            'message' => 'Other tenant notification',
        ]);

        self::assertSame($other->id, $otherComment->tenant_id);
        self::assertSame($other->id, $otherNotification->tenant_id);
        self::assertSame([$otherComment->id], DocumentReviewComment::query()->where('body', 'like', '%tenant review comment')->pluck('id')->all());
        self::assertSame([$otherNotification->id], AdminNotification::query()->where('type', 'collaboration.review')->pluck('id')->all());

        $context->set($primary);
        self::assertSame([$primaryComment->id], DocumentReviewComment::query()->where('body', 'like', '%tenant review comment')->pluck('id')->all());
        self::assertSame([$primaryNotification->id], AdminNotification::query()->where('type', 'collaboration.review')->pluck('id')->all());
    }

    public function test_ambiguous_legacy_notification_without_tenant_fails_closed(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other legacy notification tenant', 'other-legacy-notification-tenant');
        $user = User::factory()->create([
            'name' => 'Ambiguous Notification User',
            'email' => 'ambiguous-notification@example.test',
            'status' => 'active',
        ]);
        $this->addMember($primary, $user);
        $this->addMember($other, $user);
        app(TenantContext::class)->set($primary);

        DB::table('nx_admin_notifications')->insert([
            'tenant_id' => null,
            'user_id' => $user->id,
            'type' => 'legacy.ambiguous',
            'title' => 'Ambiguous legacy notification',
            'message' => 'Must not be visible in any tenant context.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::assertSame(0, AdminNotification::query()->where('type', 'legacy.ambiguous')->count());

        app(TenantContext::class)->set($other);
        self::assertSame(0, AdminNotification::query()->where('type', 'legacy.ambiguous')->count());
        self::assertSame(1, AdminNotification::query()->withoutGlobalScope('nexora_tenant')->where('type', 'legacy.ambiguous')->count());
    }

    private function createDocument(string $title): Document
    {
        return Document::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => 'page',
            'status' => 'draft',
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'content' => ['version' => 1, 'blocks' => []],
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
