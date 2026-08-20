<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V522SemanticLockReproducibilityArchitectureTest extends TestCase
{
    #[Test]
    public function independent_lock_generation_uses_semantic_reproducibility_without_weakening_raw_candidate_sealing(): void
    {
        require_once base_path('scripts/lib/n1-target-semantic-lock-reproducibility-contracts.php');
        $result = \nexoraAnalyzeSemanticLockReproducibilityContracts(base_path());
        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['independent_generation_runs']);
        self::assertSame(2, $result['metrics']['raw_hashes_recorded']);
        self::assertSame(2, $result['metrics']['semantic_hashes_compared']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
    }
}
