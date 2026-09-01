<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

final class N100V30DistributedUpgradeArchitectureTest extends TestCase
{
    public function test_distributed_upgrade_is_fenced_and_operator_controlled(): void
    {
        $cluster=(string)file_get_contents(base_path('app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php'));
        $manager=(string)file_get_contents(base_path('app/Nexora/Foundation/Upgrade/UpgradeManager.php'));
        $ledger=(string)file_get_contents(base_path('app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php'));
        $heartbeat=(string)file_get_contents(base_path('app/Http/Middleware/RuntimeNodeHeartbeat.php'));
        $leadership=(string)file_get_contents(base_path('app/Nexora/Cloud/Services/ClusterLeadership.php'));
        $provider=(string)file_get_contents(base_path('app/Providers/AppServiceProvider.php'));

        self::assertStringContainsString('RuntimeLeaseManager',$cluster);
        self::assertStringContainsString('recovery_required',$cluster);
        self::assertStringContainsString('APP_MAINTENANCE_DRIVER=cache',$cluster);
        self::assertStringNotContainsString("setStatus('draining')",$cluster);
        self::assertStringContainsString('compatibility_assessment_sha256',$manager);
        self::assertStringContainsString('migration_ledger_converged',$manager);
        self::assertStringContainsString('cluster->holdForRecovery',$manager);
        self::assertStringContainsString('assertUnchanged',$ledger);
        self::assertStringContainsString('assertConverged',$ledger);
        self::assertStringContainsString("503",$heartbeat);
        self::assertStringContainsString('$this->nodes->isReady()',$leadership);
        self::assertStringContainsString('$this->versions->compatible()',$leadership);
        self::assertMatchesRegularExpression("/app\\('queue\\.worker'\\)->shouldQuit\\s*=\\s*true;/",$provider);
    }
}
