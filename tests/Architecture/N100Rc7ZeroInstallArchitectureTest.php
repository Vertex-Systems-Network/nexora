<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc7ZeroInstallArchitectureTest extends TestCase
{
    public function test_rc7_zero_install_and_recovery_contracts_are_source_clean(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root.'/scripts/lib/zero-install-contracts.php';
        $result = \nexoraAnalyzeZeroInstallContracts($root);

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(15, $result['metrics']['required_artifacts']);
        self::assertSame(3, $result['metrics']['setup_runners']);
        self::assertSame(2, $result['metrics']['recovery_layers']);

        $config = (string) file_get_contents($root.'/config/nexora.php');
    }
}
