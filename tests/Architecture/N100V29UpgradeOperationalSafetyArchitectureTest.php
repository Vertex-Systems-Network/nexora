<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100V29UpgradeOperationalSafetyArchitectureTest extends TestCase
{
    public function test_upgrade_requires_restore_readiness_maintenance_ownership_health_and_operator_recovery_decision(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/upgrade-contracts.php';
        $result=\nexoraAnalyzeUpgradeContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(1,$result['metrics']['restore_readiness_gate']);
        self::assertSame(1,$result['metrics']['maintenance_ownership']);
        self::assertSame(2,$result['metrics']['post_upgrade_health']);
        self::assertSame(1,$result['metrics']['recovery_decision_record']);
        self::assertSame(0,$result['metrics']['automatic_database_rollback']);

        $manager=(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');
        self::assertStringContainsString('maintenanceLease->acquire',$manager);
        self::assertStringContainsString('post_upgrade_health_passed',$manager);
        self::assertStringContainsString('post_metadata_health_passed',$manager);
        self::assertStringContainsString('post-commit bookkeeping',$manager);
        self::assertStringNotContainsString('migrate:rollback',$manager);
    }
}
