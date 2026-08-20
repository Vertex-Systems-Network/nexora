<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Nexora\Cloud\Services\ClusterRehearsalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ClusterRehearsalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_cluster_rehearsal_proves_lease_exclusion_and_failover(): void
    {
        Storage::fake('local');
        config()->set('nexora_cloud.object_storage_disk','local');
        config()->set('cache.default','array');
        $result=app(ClusterRehearsalService::class)->run();
        self::assertSame('pass',$result['status']);
        self::assertTrue($result['lease_exclusion']['owner_a_acquired']);
        self::assertTrue($result['lease_exclusion']['owner_b_blocked']);
        self::assertTrue($result['lease_exclusion']['owner_b_failover']);
    }
}
