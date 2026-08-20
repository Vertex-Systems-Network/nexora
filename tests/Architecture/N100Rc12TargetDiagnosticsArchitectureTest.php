<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc12TargetDiagnosticsArchitectureTest extends TestCase
{
    public function test_rc12_target_diagnostics_capture_is_safe_and_complete(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/target-diagnostics-contracts.php';
        $result=\nexoraAnalyzeTargetDiagnosticsContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(6, $result['metrics']['diagnostic_groups']);

        $runner=(string)file_get_contents($root.'/scripts/target-diagnostics.php');
        foreach(['--install-deps','--full','summary.json','summary.md','package:discover','npm run build','final-closure-status.php','[REDACTED]'] as $marker) {
            self::assertStringContainsString($marker,$runner);
        }
        self::assertStringNotContainsString('phpinfo()',$runner);
    }
}
