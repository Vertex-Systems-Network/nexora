<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V522TypeScriptDepthArchitectureTest extends TestCase
{
    #[Test]
    public function recursive_inertia_form_payloads_have_explicit_depth_boundaries(): void
    {
        require_once base_path('scripts/lib/n1-target-typescript-depth-contracts.php');
        $result = \nexoraAnalyzeTypeScriptDepthContracts(base_path());
        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(4, $result['metrics']['observed_ts2589_errors']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
    }
}
