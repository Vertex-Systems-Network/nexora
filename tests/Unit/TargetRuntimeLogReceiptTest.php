<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-log-receipt-contracts.php';

final class TargetRuntimeLogReceiptTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/nexora-log-receipt-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/steps', 0700, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root) || is_link($this->root)) return;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir() && !$file->isLink()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($this->root);
    }

    /** @return list<array<string,mixed>> */
    private function steps(string $id = 'frontend-build', string $out = "PASS\n", string $err = ''): array
    {
        file_put_contents($this->root.'/steps/'.$id.'.stdout.log', $out);
        file_put_contents($this->root.'/steps/'.$id.'.stderr.log', $err);
        return [[
            'id' => $id,
            'stdout_log' => 'steps/'.$id.'.stdout.log',
            'stderr_log' => 'steps/'.$id.'.stderr.log',
            'stdout_sha256' => hash('sha256', $out),
            'stderr_sha256' => hash('sha256', $err),
        ]];
    }

    #[Test]
    public function intact_logs_match_producer_sha256(): void
    {
        self::assertSame([], \nexoraTargetRuntimeLogReceiptErrors($this->steps(), $this->root));
    }

    #[Test]
    public function changed_stdout_is_rejected_even_if_summary_passed(): void
    {
        $steps = $this->steps();
        file_put_contents($this->root.'/steps/frontend-build.stdout.log', "PASS\nchanged\n");
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }

    #[Test]
    public function changed_stderr_and_deleted_log_are_rejected(): void
    {
        $steps = $this->steps();
        file_put_contents($this->root.'/steps/frontend-build.stderr.log', 'injected');
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
        unlink($this->root.'/steps/frontend-build.stderr.log');
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }

    #[Test]
    public function missing_or_malformed_checksum_is_rejected(): void
    {
        $steps = $this->steps();
        unset($steps[0]['stdout_sha256']);
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
        $steps = $this->steps();
        $steps[0]['stdout_sha256'] = str_repeat('G', 64);
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }

    #[Test]
    public function log_path_swap_is_rejected_even_if_checksums_match(): void
    {
        $steps = $this->steps();
        $steps[0]['stdout_log'] = $steps[0]['stderr_log'];
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
        $steps = $this->steps();
        $steps[0]['stdout_log'] = '../outside.log';
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }

    #[Test]
    public function duplicate_ids_and_non_list_receipts_fail_closed(): void
    {
        $steps = $this->steps();
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors([$steps[0], $steps[0]], $this->root));
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors(['x' => $steps[0]], $this->root));
    }

    #[Test]
    public function a_resumed_step_with_an_empty_stderr_log_is_supported(): void
    {
        $message = "Resumed from 20261016-050000-a1b2c3\n";
        self::assertSame([], \nexoraTargetRuntimeLogReceiptErrors($this->steps('npm-ci', $message, ''), $this->root));
    }

    #[Test]
    public function symlinked_step_log_and_parent_directory_fail_closed(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unavailable.');
        $steps = $this->steps();
        unlink($this->root.'/steps/frontend-build.stdout.log');
        if (!@symlink('/etc/hosts', $this->root.'/steps/frontend-build.stdout.log')) {
            self::markTestSkipped('Symlink creation not permitted.');
        }
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
        unlink($this->root.'/steps/frontend-build.stdout.log');
        rename($this->root.'/steps', $this->root.'/real-steps');
        if (!@symlink($this->root.'/real-steps', $this->root.'/steps')) {
            self::markTestSkipped('Symlink creation not permitted.');
        }
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }

    #[Test]
    public function oversized_log_is_rejected(): void
    {
        $steps = $this->steps();
        $path = $this->root.'/steps/frontend-build.stdout.log';
        $handle = fopen($path, 'c');
        ftruncate($handle, 16*1024*1024+1);
        fclose($handle);
        self::assertNotSame([], \nexoraTargetRuntimeLogReceiptErrors($steps, $this->root));
    }
}
