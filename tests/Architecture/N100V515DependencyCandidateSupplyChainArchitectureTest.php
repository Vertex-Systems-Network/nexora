<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V515DependencyCandidateSupplyChainArchitectureTest extends TestCase
{
    #[Test]
    public function dependency_candidates_are_provenance_and_audit_bound_before_promotion(): void
    {
        require_once base_path('scripts/lib/n1-target-dependency-candidate-supply-chain-contracts.php');
        $result = \nexoraAnalyzeDependencyCandidateSupplyChainContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['candidate_generation_runs']);
        self::assertSame(2, $result['metrics']['candidate_audit_ecosystems']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
