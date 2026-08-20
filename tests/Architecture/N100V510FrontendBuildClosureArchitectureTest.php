<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V510FrontendBuildClosureArchitectureTest extends TestCase
{
    #[Test]
    public function frontend_build_failures_are_diagnostic_without_moving_c1_goalposts(): void
    {
        require_once base_path('scripts/lib/n1-target-frontend-build-closure-contracts.php');
        $result = \nexoraAnalyzeFrontendBuildClosureContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(76, $result['metrics']['historical_errors']);
        self::assertSame(11, $result['metrics']['historical_files']);
        self::assertSame(14, $result['metrics']['c1_target_gates']);
        self::assertSame(105, $result['metrics']['target_denominator']);
        self::assertSame(0, $result['metrics']['target_denominator_changed']);
    }
}
