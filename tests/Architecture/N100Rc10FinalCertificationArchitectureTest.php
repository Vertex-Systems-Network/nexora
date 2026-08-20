<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc10FinalCertificationArchitectureTest extends TestCase
{
    public function test_rc10_backup_ha_and_final_evidence_contracts_are_present(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/ha-final-contracts.php';
        $result=\nexoraAnalyzeHaFinalContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));

        $runner=(string)file_get_contents($root.'/scripts/certify-release.php');
        $builder=(string)file_get_contents($root.'/scripts/build-production-release.php');
        self::assertStringContainsString('NEXORA_CERT_FINAL_EVIDENCE',$runner);
        self::assertStringContainsString('ha-evidence-verify.php',$runner);
        self::assertStringContainsString('backup-restore-evidence-verify.php',$runner);
        self::assertStringContainsString('final-evidence-verify.php',$runner);
        self::assertStringContainsString('final_evidence_report_sha256',$builder);
    }
}
