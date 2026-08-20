<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Pkg1UsableClosureArchitectureTest extends TestCase
{
    #[Test]
    public function pkg1_remains_one_resumable_usable_release_boundary_without_new_target_gates(): void
    {
        require_once base_path('scripts/lib/pkg1-closure-contracts.php');
        $result = \nexoraAnalyzePkg1ClosureContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(14, $result['metrics']['c1_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
