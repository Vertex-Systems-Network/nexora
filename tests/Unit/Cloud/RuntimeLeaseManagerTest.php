<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Models\RuntimeLease;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RuntimeLeaseManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_lease_allows_one_owner_until_expiry(): void
    {
        $leases = app(RuntimeLeaseManager::class);
        self::assertTrue($leases->acquireOrRenew('scheduler-leader', 'node-a', 90));
        self::assertTrue($leases->acquireOrRenew('scheduler-leader', 'node-a', 90));
        self::assertFalse($leases->acquireOrRenew('scheduler-leader', 'node-b', 90));

        RuntimeLease::query()->where('name', 'scheduler-leader')->update(['expires_at' => now()->subSecond()]);
        self::assertTrue($leases->acquireOrRenew('scheduler-leader', 'node-b', 90));
        self::assertSame('node-b', RuntimeLease::query()->where('name', 'scheduler-leader')->value('owner_node_key'));
    }
}
