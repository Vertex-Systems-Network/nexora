<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RunSeoCrawlJob;
use App\Models\Document;
use App\Models\Role;
use App\Models\SearchIndexEntry;
use App\Models\SearchQueryLog;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class DiscoveryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_documents_are_indexed_and_public_search_records_privacy_aware_query_demand(): void
    {
        $document = Document::query()->create([
            'uuid'=>(string) \Illuminate\Support\Str::uuid(),'type'=>'article','status'=>'published','workflow_status'=>'published',
            'title'=>'Searchable Nexora Architecture','slug'=>'searchable-nexora-architecture','excerpt'=>'A precise architecture article.',
            'content'=>['version'=>1,'blocks'=>[['id'=>'paragraph_0001','type'=>'paragraph','version'=>1,'data'=>['text'=>'Search indexing should find this unique foundation phrase.'],'children'=>[]]]],
            'metadata'=>[],'schema_version'=>1,'lock_version'=>1,'published_at'=>now(),
        ]);

        $this->assertDatabaseHas('nx_search_index', ['resource_type'=>'document','resource_id'=>$document->id,'status'=>'published']);
        self::assertStringContainsString('unique foundation phrase', (string) SearchIndexEntry::query()->where('resource_id',$document->id)->value('body_text'));

        $response = $this->get('/search?q=foundation+phrase');
        $response->assertOk()->assertSee('Searchable Nexora Architecture');
        self::assertSame(1, SearchQueryLog::query()->where('normalized_query','foundation phrase')->count());
    }

    public function test_administrator_can_manage_discovery_and_queue_same_host_crawl(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Queue::fake();
        $admin = User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->get('/admin/discovery')->assertOk();
        $this->actingAs($admin)->post('/admin/discovery/reindex')->assertRedirect();
        $this->actingAs($admin)->post('/admin/discovery/aggregate')->assertRedirect();
        $this->actingAs($admin)->post('/admin/discovery/crawl', ['limit'=>25])->assertRedirect();
        Queue::assertPushed(RunSeoCrawlJob::class);
        $this->assertDatabaseHas('nx_crawl_runs', ['status'=>'queued','requested_limit'=>25]);
    }
}
