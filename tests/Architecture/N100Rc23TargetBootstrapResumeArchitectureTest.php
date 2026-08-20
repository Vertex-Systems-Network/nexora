<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc23TargetBootstrapResumeArchitectureTest extends TestCase
{
    #[Test]
    public function rc23_bootstrap_resume_and_evidence_boundaries_are_fail_closed(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/target-resume-contracts.php';
        $result=\nexoraAnalyzeTargetResumeContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(3,$result['metrics']['bootstrap_wrappers']);
        $runner=(string)file_get_contents($root.'/scripts/target-runtime-run.php');
        self::assertStringContainsString('--resume-latest',$runner);
        self::assertStringContainsString('source_tree_sha256',$runner);
        self::assertStringContainsString('vendor_installed_sha256',$runner);
        $evidence=(string)file_get_contents($root.'/scripts/target-runtime-evidence-verify.php');
        self::assertStringContainsString('--require-pass',$evidence);
        self::assertStringContainsString('Unsafe ZIP path',$evidence);
    }
}
