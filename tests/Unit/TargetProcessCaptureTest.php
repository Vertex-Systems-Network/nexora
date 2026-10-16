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
    public function closed_output_pipes_must_not_bypass_timeout(): void
    {
        $started = microtime(true);
        $result = $this->capture([PHP_BINARY, '-r',
            'fclose(STDOUT); fclose(STDERR); sleep(4);'], 1024, 1);
        self::assertNotSame(0, $result['exit_code']);
        self::assertStringContainsString('timed out', $result['stderr']);
        self::assertLessThan(3.0, microtime(true) - $started,
            'Child closing output streams must not circumvent the deadline.');
    }

    #[Test]
    public function timeout_force_stops_a_child_that_ignores_sigterm(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || !extension_loaded('pcntl')) {
            self::markTestSkipped('POSIX signal regression requires pcntl.');
        }
        $started = microtime(true);
        $code = 'pcntl_async_signals(true); pcntl_signal(SIGTERM, SIG_IGN); '
            .'fclose(STDOUT); fclose(STDERR); sleep(5);';
        $result = $this->capture([PHP_BINARY, '-r', $code], 1024, 1);
        self::assertNotSame(0, $result['exit_code']);
        self::assertStringContainsString('timed out', $result['stderr']);
        self::assertLessThan(3.0, microtime(true) - $started,
            'SIGTERM-resistant subprocess must not block finalization.');
    }

    #[Test]
    public function child_exit_code_is_retained_after_both_pipes_close(): void
    {
        $result = $this->capture([PHP_BINARY, '-r',
            'fclose(STDOUT); fclose(STDERR); exit(37);'], 1024, 2);
        self::assertSame(37, $result['exit_code']);
    }

    #[Test]
    public function stalled_process_is_terminated(): void
    {
        $result = $this->capture([PHP_BINARY, '-r', 'sleep(3);'], 1024, 1);
        self::assertNotSame(0, $result['exit_code']);
        self::assertStringContainsString('timed out', $result['stderr']);
    }
}
