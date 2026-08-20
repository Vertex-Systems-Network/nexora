<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc11FinalTargetArchitectureTest extends TestCase
{
    public function test_rc11_final_target_closure_harness_is_fail_closed(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/final-closure-contracts.php';
        $result=\nexoraAnalyzeFinalClosureContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));

        $runner=(string)file_get_contents($root.'/scripts/final-target-run.php');
        $closure=(string)file_get_contents($root.'/scripts/lib/final-closure.php');
        foreach(['--final','--status-only','--install-deps','NEXORA_CERT_FINAL_EVIDENCE','certify-release.php'] as $marker) self::assertStringContainsString($marker,$runner);
        foreach(['automated_certification','build_assets','http_performance','browser','backup_restore','multi_node_ha','final_evidence','production_package','n1_0_done'] as $marker) self::assertStringContainsString($marker,$closure);
    }
}
