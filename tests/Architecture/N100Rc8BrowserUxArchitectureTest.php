<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc8BrowserUxArchitectureTest extends TestCase
{
    public function test_rc8_browser_ux_contracts_are_present(): void
    {
        $root=dirname(__DIR__,2);
        $runner=(string)file_get_contents($root.'/scripts/certify-release.php');
        $layout=(string)file_get_contents($root.'/resources/js/admin/layout/AdminLayout.tsx');
        $css=(string)file_get_contents($root.'/resources/css/app.css');
        $table=(string)file_get_contents($root.'/resources/js/admin/components/data/DataTable.tsx');
        self::assertStringContainsString('browser-ux-contract-verify.php',$runner);
        self::assertStringContainsString('browser-evidence-verify.php',$runner);
        self::assertStringContainsString('href="#nexora-main-content"',$layout);
        self::assertStringContainsString('id="nexora-main-content"',$layout);
        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)',$css);
        self::assertStringContainsString('@media (forced-colors: active)',$css);
        self::assertStringContainsString('[dir="rtl"] .nx-route-progress',$css);
        self::assertStringContainsString('aria-sort={ariaSort}',$table);
        self::assertStringContainsString('aria-label="Table pagination"',$table);
    }
}
