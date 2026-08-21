<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use App\Jobs\ProcessContentMigrationJob;
use App\Models\ContentMigrationRun;
use App\Models\Document;
use App\Models\EnterpriseOrganization;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Migrations\Services\ContentExportService;
use App\Nexora\Migrations\Services\ContentMigrationManager;
use App\Nexora\Migrations\Services\WordPressContentImporter;
use App\Nexora\Migrations\WordPress\WordPressWxrReader;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ContentMigrationProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
        Storage::fake('local');
    }

    public function test_wordpress_wxr_is_staged_on_server_path_deduplicated_and_imported_through_tenant_document_engine(): void
    {
        if (! class_exists(\XMLReader::class)) {
            $this->markTestSkipped('XMLReader is required for the WordPress WXR acceptance flow.');
        }

        Queue::fake();
        $organization = $this->defaultOrganization();
        $actor = User::factory()->create(['status' => 'active']);
        app(TenantContext::class)->set($organization);
        $manager = app(ContentMigrationManager::class);

        $run = $manager->stageWordPressWxr(
            UploadedFile::fake()->createWithContent('../../dangerous.xml', $this->wxr()),
            $actor,
        );
        $duplicate = $manager->stageWordPressWxr(
            UploadedFile::fake()->createWithContent('copy.xml', $this->wxr()),
            $actor,
        );

        self::assertSame($run->id, $duplicate->id);
        self::assertStringStartsWith('nexora/migrations/'.$organization->id.'/', $run->source_path);
        self::assertStringNotContainsString('dangerous', $run->source_path);
        Storage::disk('local')->assertExists($run->source_path);

        (new ProcessContentMigrationJob($run->id))->handle(
            app(TenantExecutionScope::class),
            app(WordPressWxrReader::class),
            app(WordPressContentImporter::class),
        );

        $run->refresh();
        self::assertSame('completed', $run->status);
        self::assertSame(1, $run->imported_items);
        self::assertSame(0, $run->failed_items);
        Storage::disk('local')->assertMissing($run->source_path);

        $document = Document::query()->where('slug', 'hello-world')->firstOrFail();
        self::assertSame('blog_post', $document->type);
        self::assertSame('published', $document->status);
        self::assertSame('wordpress-wxr-v1', $document->metadata['migration']['engine'] ?? null);
        self::assertFalse((bool) ($document->metadata['migration']['remote_media_fetch'] ?? true));
        self::assertStringNotContainsString('<script', json_encode($document->content, JSON_THROW_ON_ERROR));
    }

    public function test_failed_item_state_is_persisted_without_creating_a_destination_document(): void
    {
        $organization = $this->defaultOrganization();
        $actor = User::factory()->create(['status' => 'active']);
        app(TenantContext::class)->set($organization);
        $run = $this->run($organization, $actor);

        $outcome = app(WordPressContentImporter::class)->import($run, [
            'source_key' => 'wordpress:post:oversized',
            'post_type' => 'post',
            'post_id' => '999',
            'post_name' => 'oversized',
            'title' => 'Oversized',
            'status' => 'draft',
            'content' => str_repeat('x', 2_097_153),
            'terms' => [],
        ]);

        self::assertSame('failed', $outcome);
        $this->assertDatabaseHas('nx_content_migration_items', [
            'migration_run_id' => $run->id,
            'source_key' => 'wordpress:post:oversized',
            'status' => 'failed',
            'destination_id' => null,
        ]);
        self::assertSame(0, Document::query()->where('title', 'Oversized')->count());
    }

    public function test_streaming_export_contains_only_the_active_tenant_documents(): void
    {
        $first = $this->defaultOrganization();
        $second = EnterpriseOrganization::query()->create([
            'id' => (string) Str::uuid(), 'name' => 'Other tenant', 'slug' => 'other-tenant',
            'status' => 'active', 'is_default' => false, 'timezone' => 'UTC', 'locale' => 'en',
        ]);
        $actor = User::factory()->create(['status' => 'active']);

        app(TenantContext::class)->set($first);
        Document::factory()->for($actor, 'author')->create(['title' => 'First tenant export', 'slug' => 'first-tenant-export']);
        app(TenantContext::class)->set($second);
        Document::factory()->for($actor, 'author')->create(['title' => 'Other tenant secret', 'slug' => 'other-tenant-secret']);
        app(TenantContext::class)->set($first);

        $response = app(ContentExportService::class)->documents();
        ob_start();
        $response->sendContent();
        $json = (string) ob_get_clean();

        self::assertStringContainsString('First tenant export', $json);
        self::assertStringNotContainsString('Other tenant secret', $json);
        self::assertStringContainsString('nexora.documents.export.v1', $json);
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function run(EnterpriseOrganization $organization, User $actor): ContentMigrationRun
    {
        return ContentMigrationRun::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $organization->id,
            'created_by' => $actor->id,
            'source_type' => 'wordpress_wxr',
            'source_name' => 'test.xml',
            'source_path' => 'nexora/migrations/test.xml',
            'source_hash' => hash('sha256', 'test-'.Str::uuid()),
            'source_bytes' => 128,
            'status' => 'running',
        ]);
    }

    private function wxr(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
 xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
 xmlns:content="http://purl.org/rss/1.0/modules/content/"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
 <wp:wxr_version>1.2</wp:wxr_version>
 <item>
  <title>Hello World</title>
  <link>https://example.test/hello-world/</link>
  <dc:creator>editor</dc:creator>
  <guid isPermaLink="false">https://example.test/?p=11</guid>
  <content:encoded><![CDATA[<p>Hello <strong>world</strong>.</p><script>alert(1)</script>]]></content:encoded>
  <excerpt:encoded><![CDATA[Short excerpt]]></excerpt:encoded>
  <wp:post_id>11</wp:post_id>
  <wp:post_date>2026-01-02 03:04:05</wp:post_date>
  <wp:status>publish</wp:status>
  <wp:post_name>hello-world</wp:post_name>
  <wp:post_type>post</wp:post_type>
  <category domain="category" nicename="news"><![CDATA[News]]></category>
 </item>
</channel>
</rss>
XML;
    }
}
