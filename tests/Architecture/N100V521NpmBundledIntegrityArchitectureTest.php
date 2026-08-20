<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V521NpmBundledIntegrityArchitectureTest extends TestCase
{
    #[Test]
    public function npm_bundled_children_are_accepted_only_under_integrity_bound_owners(): void
    {
        require_once base_path('scripts/lib/n1-target-npm-bundled-integrity-contracts.php');
        $result = \nexoraAnalyzeNpmBundledIntegrityContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(6, $result['metrics']['bundled_fixture_children']);
        self::assertSame(3, $result['metrics']['negative_fail_closed_fixtures']);
        self::assertSame(14, $result['metrics']['c1_certification_gates']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
    }
}
