<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Models\Document;
use App\Models\Role;
use App\Models\SeoEntry;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoCoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_save_document_seo_and_public_runtime_emits_resolved_metadata(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $document = Document::factory()->create([
            'author_id' => $admin->id,
            'last_edited_by' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
            'title' => 'SEO Core Test',
            'slug' => 'seo-core-test',
            'excerpt' => 'Default document excerpt.',
        ]);

        $this->actingAs($admin)->put("/admin/seo/documents/{$document->id}", [
            'seo_title' => 'SEO Core Test Resolved',
            'meta_description' => 'A test of Nexora SEO metadata.',
            'canonical_url' => 'https://example.test/seo-core-test',
            'url_path' => '/seo-core-test',
            'robots_index' => true,
            'robots_follow' => true,
            'robots_directives' => ['noarchive'],
            'schema_type' => 'WebPage',
            'sitemap_include' => true,
            'social_title' => 'Social SEO Core Test',
            'social_description' => 'Social sharing description.',
            'social_image' => 'https://cdn.example.test/social.jpg',
            'social_image_media_id' => null,
        ])->assertSessionHasNoErrors();

        self::assertSame(1, SeoEntry::query()->where('resource_type', 'document')->where('resource_id', $document->id)->count());
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://example.test/seo-core-test', false);

        $this->get('/content/seo-core-test')
            ->assertOk()
            ->assertSee('<title>SEO Core Test Resolved</title>', false)
            ->assertSee('content="index,follow,noarchive"', false)
            ->assertSee('property="og:title" content="Social SEO Core Test"', false)
            ->assertSee('property="og:description" content="Social sharing description."', false)
            ->assertSee('property="og:image" content="https://cdn.example.test/social.jpg"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('name="twitter:title" content="Social SEO Core Test"', false);
    }
}
