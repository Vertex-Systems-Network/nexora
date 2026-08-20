<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V58ClockTempPortabilityArchitectureTest extends TestCase
{
    #[Test]
    public function installer_clock_and_temp_portability_boundary_is_fail_closed_without_false_windows_blockers(): void
    {
        require_once base_path('scripts/lib/n1-target-clock-temp-portability-contracts.php');
        $result = \nexoraAnalyzeClockTempPortabilityContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(0, $result['metrics']['mysql_timezone_double_conversion_blockers']);
        self::assertSame(5000, $result['metrics']['strict_clock_default_ms']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
