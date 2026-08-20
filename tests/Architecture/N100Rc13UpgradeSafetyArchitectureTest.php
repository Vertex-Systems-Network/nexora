<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc13UpgradeSafetyArchitectureTest extends TestCase
{
    public function test_rc13_existing_install_upgrade_safety_contract_is_fail_closed(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/upgrade-contracts.php';
        $result=\nexoraAnalyzeUpgradeContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertGreaterThanOrEqual(4, $result['metrics']['commands']);
        self::assertSame(0, $result['metrics']['automatic_database_rollback']);

        $manager=(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');
        self::assertStringContainsString("Artisan::call('down')",$manager);
        self::assertStringContainsString("Artisan::call('migrate'",$manager);
        self::assertStringNotContainsString('migrate:rollback',$manager);
        self::assertStringNotContainsString('migrate:fresh',$manager);
    }
}
