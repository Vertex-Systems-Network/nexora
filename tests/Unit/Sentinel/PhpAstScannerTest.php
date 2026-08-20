<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\PhpAstScanner;
use PHPUnit\Framework\TestCase;

final class PhpAstScannerTest extends TestCase
{
    public function test_ast_detects_backtick_shell_and_dynamic_invocation(): void
    {
        $source = <<<'PHP'
<?php
$output = `whoami`;
$callable = $_GET['fn'];
$callable('value');
PHP;

        $findings = (new PhpAstScanner())->scan('src/Dangerous.php', $source);
        $rules = array_map(static fn ($finding): string => $finding->ruleId, $findings);

        self::assertContains('NEX-PHP-0008', $rules);
        self::assertContains('NEX-AST-0002', $rules);
    }

    public function test_ast_parse_failure_fails_closed(): void
    {
        $findings = (new PhpAstScanner())->scan('src/Broken.php', "<?php function broken( {\n");
        self::assertSame('NEX-AST-0001', $findings[0]->ruleId);
        self::assertTrue($findings[0]->hardBlock);
    }
}
