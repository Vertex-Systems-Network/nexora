<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Models\RuntimeLease;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\HaReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HaReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_strict_ha_readiness_requires_shared_runtime_and_multiple_matching_nodes(): void
    {
        config()->set('cache.default','database');
        config()->set('session.driver','database');
        config()->set('queue.default','database');
        config()->set('nexora_cloud.object_storage_disk','s3');
        config()->set('nexora-ha.required_nodes',2);
        foreach(['node-a','node-b'] as $key) {
            RuntimeNode::query()->create(['id'=>(string)Str::uuid(),'node_key'=>$key,'status'=>'active','role'=>'application','version'=>(string)config('nexora.version'),'last_heartbeat_at'=>now()]);
        }
        RuntimeLease::query()->create(['id'=>(string)Str::uuid(),'name'=>'scheduler-leader','owner_node_key'=>'node-a','expires_at'=>now()->addMinute(),'heartbeat_at'=>now()]);
        $result=app(HaReadinessService::class)->assess();
        self::assertTrue($result['ready']);
        self::assertSame(2,$result['node_count']);
    }
}
