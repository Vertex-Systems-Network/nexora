<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V56InstallerRuntimeReadinessArchitectureTest extends TestCase
{
    #[Test]
    public function installer_runtime_readiness_remains_early_and_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-installer-runtime-readiness-contracts.php');
        $result = \nexoraAnalyzeInstallerRuntimeReadinessContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertGreaterThanOrEqual(7, $result['metrics']['readiness_components_minimum']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
