<?php

declare(strict_types=1);

namespace Tests\Feature\Distribution;

use App\Jobs\SendNewsletterDelivery;
use App\Models\Document;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Distribution\Services\NewsletterDispatchService;
use App\Nexora\Distribution\Services\NewsletterSubscriptionManager;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DistributionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_subscriber_consent_and_campaign_queue_are_recorded(): void
    {
        Queue::fake();
        $list=NewsletterList::query()->where('slug','general-updates')->firstOrFail();
        $subscriber=app(NewsletterSubscriptionManager::class)->subscribe('reader@example.test','Reader','en','test',$list);
        self::assertSame('active',$subscriber->status);
        self::assertNotNull($subscriber->consented_at);

        $author=User::factory()->create(['email_verified_at'=>now()]);
        $document=Document::factory()->create(['type'=>'article','status'=>'published','title'=>'Distribution Architecture','slug'=>'distribution-architecture','author_id'=>$author->id,'last_edited_by'=>$author->id,'published_at'=>now()]);
        $campaign=NewsletterCampaign::query()->create([
            'uuid'=>(string) Str::uuid(),'name'=>'Launch','subject'=>'Distribution Architecture','document_id'=>$document->id,'list_id'=>$list->id,
            'status'=>'draft','created_by'=>$author->id,'metadata'=>[],
        ]);
        $count=app(NewsletterDispatchService::class)->queue($campaign);
        self::assertSame(1,$count);
        self::assertSame('sending',$campaign->fresh()->status);
        Queue::assertPushed(SendNewsletterDelivery::class, fn ($job)=>$job->subscriberId===$subscriber->id);
    }

    public function test_public_rss_contains_published_blog_content(): void
    {
        $author=User::factory()->create(['email_verified_at'=>now()]);
        Document::factory()->create(['type'=>'blog_post','status'=>'published','title'=>'RSS Test Article','slug'=>'rss-test-article','author_id'=>$author->id,'last_edited_by'=>$author->id,'published_at'=>now(),'content'=>['version'=>1,'blocks'=>[['id'=>'blockrss1','type'=>'paragraph','version'=>1,'data'=>['text'=>'Feed body'],'children'=>[]]]]]);
        $this->get('/feed.xml')->assertOk()->assertHeader('Content-Type','application/rss+xml; charset=UTF-8')->assertSee('RSS Test Article',false);
    }
}
