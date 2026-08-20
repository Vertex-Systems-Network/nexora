<?php

declare(strict_types=1);
namespace Tests\Architecture;
use Tests\TestCase;
final class N100C5BrowserAccessibilityPerformanceArchitectureTest extends TestCase
{
    public function test_c5_browser_accessibility_rtl_and_performance_contracts_remain_fail_closed(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-c5-contracts.php';$r=\nexoraAnalyzeN10C5Contracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(3,$r['metrics']['wrappers']);self::assertSame(3,$r['metrics']['browsers']);self::assertSame(36,$r['metrics']['matrix_rows']);self::assertSame(10,$r['metrics']['accessibility_checks']);self::assertSame(4,$r['metrics']['web_vital_metrics']);self::assertSame(8,$r['metrics']['evidence_bindings']);self::assertSame(0,$r['metrics']['dependency_installs']);self::assertSame(0,$r['metrics']['db_c4_ha_calls']);
    }
}
