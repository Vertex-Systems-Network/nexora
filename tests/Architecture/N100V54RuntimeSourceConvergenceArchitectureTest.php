<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V54RuntimeSourceConvergenceArchitectureTest extends TestCase
{
    #[Test]
    public function loaded_runtime_source_and_secure_web_ack_remain_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-runtime-source-convergence-contracts.php');
        $result = \nexoraAnalyzeRuntimeSourceConvergenceContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(20, $result['metrics']['critical_runtime_classes']);
        self::assertGreaterThanOrEqual(22, $result['metrics']['critical_source_files']);
        self::assertSame(1, $result['metrics']['secure_web_ack']);
        self::assertSame(1, $result['metrics']['public_source_status_redacted']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
