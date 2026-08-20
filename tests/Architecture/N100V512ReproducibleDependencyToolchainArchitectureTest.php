<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V512ReproducibleDependencyToolchainArchitectureTest extends TestCase
{
    #[Test]
    public function dependency_lock_generation_and_install_stay_reproducible_and_toolchain_bound(): void
    {
        require_once base_path('scripts/lib/n1-target-reproducible-dependency-toolchain-contracts.php');
        $result = \nexoraAnalyzeReproducibleDependencyToolchainContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['candidate_generation_runs']);
        self::assertSame(4, $result['metrics']['toolchain_bound_tools']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
