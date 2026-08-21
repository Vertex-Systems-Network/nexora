<?php

declare(strict_types=1);

namespace Tests\Feature\Cloud;

use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class DistributedRuntimeHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_lease_and_barrier_acquisition_fail_closed_when_coordination_table_is_unavailable(): void
    {
        Schema::shouldReceive('hasTable')
            ->twice()
            ->with('nx_runtime_leases')
            ->andReturn(false);

        $manager = new RuntimeLeaseManager(Mockery::mock(RuntimeHostClockIdentity::class));

        self::assertFalse($manager->acquireOrRenew('scheduler-leader', 'node-a', 90));
        self::assertFalse($manager->acquireActivityUnlessBarrierActive(
            'maintenance-worker',
            'node-a',
            90,
            ['test' => true],
            'maintenance-barrier',
        ));
    }

    public function test_runtime_lease_enforces_single_owner_and_allows_failover_after_release(): void
    {
        self::assertTrue(Schema::hasTable('nx_runtime_leases'));

        $manager = app(RuntimeLeaseManager::class);
        $lease = 'distributed-runtime-test';

        self::assertTrue($manager->acquireOrRenew($lease, 'node-a', 90, ['test' => true]));
        self::assertFalse($manager->acquireOrRenew($lease, 'node-b', 90, ['test' => true]));

        $manager->release($lease, 'node-a');

        self::assertTrue($manager->acquireOrRenew($lease, 'node-b', 90, ['test' => true]));
        $manager->release($lease, 'node-b');
    }
}
