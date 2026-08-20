<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V520WindowsNpmBridgeArchitectureTest extends TestCase
{
    #[Test]
    public function windows_npm_cmd_is_bridged_without_changing_certification_denominators(): void
    {
        require_once base_path('scripts/lib/n1-target-windows-npm-bridge-contracts.php');
        $result = \nexoraAnalyzeWindowsNpmBridgeContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(1, $result['metrics']['windows_npm_shell_bypass']);
        self::assertSame(1, $result['metrics']['npm_execution_payloads_fingerprinted']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
