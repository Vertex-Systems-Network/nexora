<?php

declare(strict_types=1);
namespace Tests\Architecture;
use Tests\TestCase;
final class N100TargetExecutionPackArchitectureTest extends TestCase
{
    public function test_target_execution_pack_delegates_all_six_chunks_without_bypass(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-target-execution-contracts.php';$r=\nexoraAnalyzeN10TargetExecutionContracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(3,$r['metrics']['wrappers']);self::assertSame(3,$r['metrics']['automated_chunks']);self::assertSame(3,$r['metrics']['operator_chunks']);self::assertGreaterThanOrEqual(12,$r['metrics']['ordered_phases']);self::assertSame(3,$r['metrics']['lock_refresh_wrappers']);self::assertSame(1,$r['metrics']['trusted_composer_discovery']);self::assertSame(1,$r['metrics']['support_capsule']);self::assertSame(0,$r['metrics']['automatic_lock_acceptance']);self::assertSame(0,$r['metrics']['direct_destructive_db_calls']);
    }
}
