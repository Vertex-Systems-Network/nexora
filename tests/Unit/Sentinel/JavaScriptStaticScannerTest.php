<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\JavaScriptStaticScanner;
use PHPUnit\Framework\TestCase;

final class JavaScriptStaticScannerTest extends TestCase
{
    public function test_dynamic_code_execution_is_blocked(): void
    {
        $findings = (new JavaScriptStaticScanner())->scan('assets/app.js', "const input = 'x'; eval(input);\n");
        self::assertNotEmpty($findings);
        self::assertSame('NEX-JS-0001', $findings[0]->ruleId);
        self::assertTrue($findings[0]->hardBlock);
    }
}
