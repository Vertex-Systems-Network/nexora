<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V55InstallerHostClockArchitectureTest extends TestCase
{
    #[Test]
    public function installer_host_clock_boundary_avoids_late_false_failures_without_weakening_certification(): void
    {
        require_once base_path('scripts/lib/n1-target-installer-host-clock-contracts.php');
        $result = \nexoraAnalyzeInstallerHostClockContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(8, $result['metrics']['installation_host_checks']);
        self::assertSame(1, $result['metrics']['strict_host_certification_preserved']);
        self::assertSame(0, $result['metrics']['windows_posix_umask_blocker']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
