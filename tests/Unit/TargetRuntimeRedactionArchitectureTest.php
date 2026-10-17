<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-contracts.php';

final class TargetRuntimeRedactionArchitectureTest extends TestCase
{
    private string $fixture;

    protected function setUp(): void
    {
        $this->fixture = sys_get_temp_dir().'/nexora-runtime-contract-'.bin2hex(random_bytes(6));
        $root = dirname(__DIR__, 2);
        mkdir($this->fixture.'/scripts/lib', 0700, true);
        foreach ([
            'scripts/target-runtime-run.php',
            'scripts/target-runtime-run.bat',
            'scripts/target-runtime-run.ps1',
            'scripts/target-runtime-run.sh',
            'scripts/target-runtime-contract-verify.php',
            'scripts/lib/target-runtime-contracts.php',
            'scripts/lib/target-runtime-redaction.php',
        ] as $relative) {
            self::assertTrue(copy($root.'/'.$relative, $this->fixture.'/'.$relative));
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->fixture) || !is_dir($this->fixture)) return;
        foreach (glob($this->fixture.'/scripts/lib/*') ?: [] as $path) unlink($path);
        rmdir($this->fixture.'/scripts/lib');
        foreach (glob($this->fixture.'/scripts/*') ?: [] as $path) {
            if (is_file($path)) unlink($path);
        }
        rmdir($this->fixture.'/scripts');
        rmdir($this->fixture);
    }

    #[Test]
    public function split_redaction_helper_satisfies_runtime_architecture_contract(): void
    {
        $result = \nexoraAnalyzeTargetRuntimeContracts($this->fixture);
        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
    }

    #[Test]
    public function removed_runtime_redactor_wiring_is_detected(): void
    {
        $file=$this->fixture.'/scripts/target-runtime-run.php';
        $source=(string)file_get_contents($file);
        self::assertStringContainsString('nexoraTargetRedactOutput(', $source);
        file_put_contents($file, str_replace('nexoraTargetRedactOutput(', 'missingRedactor(', $source));
        $result=\nexoraAnalyzeTargetRuntimeContracts($this->fixture);
        self::assertNotSame([], $result['errors']);
        self::assertStringContainsString('redaction integration', implode(' ', $result['errors']));
    }

    #[Test]
    public function stripped_redaction_helper_does_not_pass_architecture_contract(): void
    {
        $file=$this->fixture.'/scripts/lib/target-runtime-redaction.php';
        $source=(string)file_get_contents($file);
        self::assertStringContainsString('[REDACTED]', $source);
        file_put_contents($file, str_replace('[REDACTED]', 'MASK_REMOVED', $source));
        $result=\nexoraAnalyzeTargetRuntimeContracts($this->fixture);
        self::assertNotSame([], $result['errors']);
        self::assertStringContainsString('redaction helper missing safety contract', implode(' ', $result['errors']));
    }
}
