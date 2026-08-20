<?php

declare(strict_types=1);

namespace Tests\Feature\Publishing;

use App\Models\AuthorProfile;
use App\Models\ContentSeries;
use App\Models\Document;
use App\Models\Role;
use App\Models\SeoEntry;
use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Nexora\Publishing\Services\ArticlePublishingManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BlogPublishingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_article_creation_receives_default_public_url_and_article_schema_type(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Nexora Publishing Architecture',
            'slug' => 'nexora-publishing-architecture',
            'type' => 'article',
            'status' => 'draft',
            'workflow_status' => 'draft',
            'assigned_to' => null,
            'reviewer_id' => null,
            'review_due_at' => null,
            'excerpt' => 'Publishing architecture test.',
            'content' => ['version' => 1, 'blocks' => []],
        ])->assertSessionHasNoErrors();

        $document = Document::query()->where('slug', 'nexora-publishing-architecture')->firstOrFail();
        $entry = SeoEntry::query()->where('resource_type', 'document')->where('resource_id', $document->id)->firstOrFail();
        self::assertSame('/articles/nexora-publishing-architecture', $entry->url_path);
        self::assertSame('Article', $entry->schema_type);
    }

    public function test_admin_can_attach_author_taxonomy_series_and_schedule_article(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $document = Document::factory()->create(['type' => 'blog_post', 'status' => 'draft', 'author_id' => $admin->id, 'last_edited_by' => $admin->id]);
        $author = AuthorProfile::query()->create(['uuid' => (string) Str::uuid(), 'user_id' => $admin->id, 'display_name' => 'Nexora Editor', 'slug' => 'nexora-editor', 'is_public' => true]);
        $term = TaxonomyTerm::query()->create(['uuid' => (string) Str::uuid(), 'taxonomy' => 'category', 'name' => 'Engineering', 'slug' => 'engineering']);
        $series = ContentSeries::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Platform Engineering', 'slug' => 'platform-engineering', 'status' => 'active', 'metadata' => []]);

        $this->actingAs($admin)->put("/admin/publishing/articles/{$document->id}/settings", [
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'is_featured' => true,
            'featured_until' => null,
            'hero_image_url' => '',
            'source_url' => '',
            'allow_comments' => false,
            'is_sponsored' => false,
            'author_profile_ids' => [$author->id],
            'term_ids' => [$term->id],
            'series_id' => $series->id,
            'series_position' => 1,
        ])->assertSessionHasNoErrors();

        self::assertTrue((bool) $document->fresh()->articleMetadata?->is_featured);
        self::assertSame([$author->id], $document->authorProfiles()->pluck('nx_author_profiles.id')->map(fn ($id) => (int) $id)->all());
        self::assertSame([$term->id], $document->taxonomyTerms()->pluck('nx_taxonomy_terms.id')->map(fn ($id) => (int) $id)->all());
        self::assertSame($series->id, (int) $document->series()->firstOrFail()->id);
    }

    public function test_due_scheduled_draft_is_published_and_revision_is_appended(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $document = Document::factory()->create(['type' => 'article', 'status' => 'draft', 'author_id' => $admin->id, 'last_edited_by' => $admin->id, 'lock_version' => 1]);
        $document->articleMetadata()->create(['scheduled_at' => now()->subMinute(), 'is_featured' => false]);

        $count = app(ArticlePublishingManager::class)->publishScheduled();
        $fresh = $document->fresh();
        self::assertSame(1, $count);
        self::assertSame('published', $fresh->status);
        self::assertNotNull($fresh->published_at);
        self::assertSame(2, (int) $fresh->lock_version);
        self::assertSame(1, $fresh->revisions()->count());
    }
}
