<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc22TargetRuntimeClosureArchitectureTest extends TestCase
{
    #[Test]
    public function rc22_target_runtime_gate_is_fail_fast_and_isolated(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/target-runtime-contracts.php';
        $result=\nexoraAnalyzeTargetRuntimeContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(3,$result['metrics']['wrappers']);
        $runner=(string)file_get_contents($root.'/scripts/target-runtime-run.php');
        self::assertStringNotContainsString('migrate:fresh',$runner);
        self::assertStringContainsString("certify-release.php','--no-package'",$runner);
    }
}
