<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-process-capture.php';

final class TargetProcessCaptureTest extends TestCase
{
    private function capture(array $parts, int $maxBytes = 16 * 1024 * 1024, int $timeout = 10): array
    {
        return \nexoraTargetCaptureCommand($parts, dirname(__DIR__, 2), [], $maxBytes, $timeout);
    }

    #[Test]
    public function concurrent_heavy_stdout_stderr_do_not_deadlock(): void
    {
        $php = <<<'CODE'
for ($i = 0; $i < 300; $i++) { fwrite(STDOUT, str_repeat('O', 1024)); fwrite(STDERR, str_repeat('E', 1024)); }
CODE;
        $result = $this->capture([PHP_BINARY, '-r', $php]);
        self::assertSame(0, $result['exit_code'], $result['stderr']);
        self::assertSame(307200, strlen($result['stdout']));
        self::assertSame(307200, strlen($result['stderr']));
    }

    #[Test]
    public function argument_vector_preserves_spaces_and_shell_metacharacters(): void
    {
        $literal = 'literal & | ; $(echo unsafe)';
        $result = $this->capture([PHP_BINARY, '-r', 'echo $argv[1];', $literal]);
        self::assertSame(0, $result['exit_code']);
        self::assertSame($literal, $result['stdout']);
    }

    #[Test]
    public function failed_process_retains_nonzero_exit_and_stderr(): void
    {
        $result = $this->capture([PHP_BINARY, '-r', 'fwrite(STDERR,"problem"); exit(17);']);
        self::assertSame(17, $result['exit_code']);
        self::assertSame('problem', $result['stderr']);
    }

    #[Test]
    public function output_over_limit_fails_closed(): void
    {
        $result = $this->capture([PHP_BINARY, '-r', 'echo str_repeat("a",65536);'], 1024);
        self::assertNotSame(0, $result['exit_code']);
        self::assertStringContainsString('exceeded', $result['stderr']);
    }

    #[Test]
    public function stalled_process_is_terminated(): void
    {
        $result = $this->capture([PHP_BINARY, '-r', 'sleep(3);'], 1024, 1);
        self::assertNotSame(0, $result['exit_code']);
        self::assertStringContainsString('timed out', $result['stderr']);
    }
}
