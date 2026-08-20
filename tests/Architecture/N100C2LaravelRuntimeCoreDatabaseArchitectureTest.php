<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
final class N100C2LaravelRuntimeCoreDatabaseArchitectureTest extends TestCase{
 #[Test] public function c2_isolated_laravel_runtime_and_core_database_chunk_is_fail_closed():void{
  $root=base_path();require_once $root.'/scripts/lib/n1-c2-contracts.php';$r=\nexoraAnalyzeN10C2Contracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(3,$r['metrics']['wrappers']);self::assertGreaterThanOrEqual(30,$r['metrics']['ordered_gates']);self::assertSame(0,$r['metrics']['direct_dependency_installs']);self::assertSame(0,$r['metrics']['db_matrix_calls']);self::assertSame(0,$r['metrics']['operator_evidence_calls']);self::assertMatchesRegularExpression("/'version'\s*=>\s*'1\.0\.0-rc\.\d+'/",(string)file_get_contents($root.'/config/nexora.php'));self::assertStringContainsString('| N1.0 | Release Candidate certification:',(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md'));self::assertStringContainsString('CERTIFYING — N1.0-C',(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md'));
 }
}
