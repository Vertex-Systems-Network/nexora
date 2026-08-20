<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\CssStaticScanner;
use PHPUnit\Framework\TestCase;

final class CssStaticScannerTest extends TestCase
{
    public function test_remote_import_and_legacy_expression_are_detected(): void
    {
        $css = "@import url('https://evil.example/x.css');\n.x { width: expression(alert(1)); }\n";
        $findings = (new CssStaticScanner())->scan('assets/theme.css', $css);
        $rules = array_map(static fn ($finding): string => $finding->ruleId, $findings);

        self::assertContains('NEX-CSS-0001', $rules);
        self::assertContains('NEX-CSS-0003', $rules);
        self::assertTrue((bool) array_filter($findings, static fn ($finding): bool => $finding->hardBlock));
    }
}
