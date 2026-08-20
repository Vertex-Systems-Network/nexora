<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc20FinalClosureIntegrityArchitectureTest extends TestCase
{
    #[Test]
    public function rc20_final_closure_integrity_contracts_are_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/final-integrity-contracts.php';
        $result=nexoraAnalyzeFinalIntegrityContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(11,$result['metrics']['closure_domains']);
        self::assertSame(5,$result['metrics']['primary_db_families']);
        self::assertSame(6,$result['metrics']['matrix_high_risk_feature_files']);
    }
}
