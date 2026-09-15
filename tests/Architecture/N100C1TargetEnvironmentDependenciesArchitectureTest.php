<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
final class N100C1TargetEnvironmentDependenciesArchitectureTest extends TestCase{
 #[Test] public function c1_is_fail_closed_and_owns_only_target_environment_and_dependencies():void{
  $root=base_path();require_once $root.'/scripts/lib/n1-c1-contracts.php';$r=\nexoraAnalyzeN10C1Contracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(3,$r['metrics']['wrappers']);self::assertSame(14,$r['metrics']['ordered_gates']);self::assertSame(0,$r['metrics']['automatic_lock_refresh']);self::assertSame(0,$r['metrics']['automatic_lock_acceptance']);
 }
}
