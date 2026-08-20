<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
final class N100Rc25UnifiedTargetEvidenceArchitectureTest extends TestCase
{
    #[Test]
    public function rc25_unified_target_evidence_is_fail_closed_and_exact_source_bound(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/target-evidence-contracts.php';$result=\nexoraAnalyzeTargetEvidenceContracts($root);
        self::assertSame([],$result['errors'],implode("\n",$result['errors']));self::assertSame(3,$result['metrics']['wrappers']);self::assertSame(10,$result['metrics']['known_evidence']);self::assertSame(5,$result['metrics']['operator_evidence']);self::assertSame(4,$result['metrics']['fingerprint_bindings']);
        self::assertStringContainsString('target-evidence-intake.php',(string)file_get_contents($root.'/scripts/final-evidence-verify.php'));
        self::assertStringContainsString('target-evidence-intake.json',(string)file_get_contents($root.'/scripts/final-evidence-verify.php'));
    }
}
