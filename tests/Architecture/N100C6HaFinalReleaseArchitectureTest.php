<?php

declare(strict_types=1);
namespace Tests\Architecture;
use Tests\TestCase;
final class N100C6HaFinalReleaseArchitectureTest extends TestCase
{
    public function test_c6_final_release_boundary_remains_fail_closed(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-c6-contracts.php';$r=\nexoraAnalyzeN10C6Contracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(3,$r['metrics']['wrappers']);self::assertSame(5,$r['metrics']['prior_chunks']);self::assertGreaterThanOrEqual(16,$r['metrics']['ha_checks']);self::assertGreaterThanOrEqual(14,$r['metrics']['ordered_gates']);self::assertSame(21,$r['metrics']['evidence_bindings']);self::assertSame(0,$r['metrics']['direct_dependency_lock_mutations']);self::assertSame(0,$r['metrics']['direct_destructive_db_calls']);
    }
}
