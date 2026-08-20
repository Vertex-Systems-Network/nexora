<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\PhpStaticScanner;
use PHPUnit\Framework\TestCase;

final class PhpStaticScannerTest extends TestCase
{
    public function test_eval_and_shell_execution_are_high_confidence_blockers(): void
    {
        $source = <<<'PHP'
<?php
$payload = base64_decode($_POST['payload']);
eval($payload);
shell_exec('whoami');
PHP;

        $findings = (new PhpStaticScanner())->scan('src/Backdoor.php', $source);
        $rules = array_column(array_map(static fn ($finding): array => $finding->toArray(), $findings), 'rule_id');

        self::assertContains('NEX-PHP-0000', $rules);
        self::assertContains('NEX-PHP-0002', $rules);
        self::assertContains('NEX-PHP-0066', $rules);
        self::assertTrue((bool) array_filter($findings, static fn ($finding): bool => $finding->hardBlock));
    }

    public function test_safe_php_does_not_generate_findings(): void
    {
        $source = "<?php\nfinal class SafeValue { public function value(): string { return 'ok'; } }\n";
        self::assertSame([], (new PhpStaticScanner())->scan('src/SafeValue.php', $source));
    }
}
