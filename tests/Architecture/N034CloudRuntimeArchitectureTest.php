<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N034CloudRuntimeArchitectureTest extends TestCase
{
    public function test_cloud_runtime_foundations_and_boundaries_are_present(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_16_002100_add_nexora_cloud_runtime.php');
        $console = (string) file_get_contents($root.'/routes/console.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('CloudRuntimeModule::class', $config);
        foreach (['nx_runtime_nodes','nx_runtime_leases','nx_runtime_metrics','nx_runtime_backup_runs','nx_runtime_restore_plans'] as $table) self::assertStringContainsString($table, $migration);
        self::assertStringNotContainsString('->after(', $migration);
        self::assertStringContainsString('scheduler-leader', (string) file_get_contents($root.'/app/Nexora/Cloud/Services/ClusterLeadership.php'));
        self::assertStringContainsString('Cache::lock', (string) file_get_contents($root.'/app/Nexora/Cloud/Services/LaravelDistributedLock.php'));
        self::assertStringContainsString('ObjectStorageContract', (string) file_get_contents($root.'/app/Nexora/Cloud/Services/LaravelObjectStorage.php'));
        self::assertStringContainsString('nexora:node:heartbeat', $console);
        self::assertStringContainsString('when($leaderCheck)', $console);
        self::assertStringContainsString('| N0.34 | Cloud/HA/distributed runtime, queues, object storage, operational tooling | DONE |', $plan);
        self::assertStringContainsString('| N1.0 | Release Candidate certification', $plan);
    }
}
