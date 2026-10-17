<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-resume-input.php';

final class TargetRuntimeResumeReceiptIntegrityTest extends TestCase
{
    private const RUN = '20261016-123000-a1b2c3';
    private string $runDir;

    protected function setUp(): void
    {
        $this->runDir = sys_get_temp_dir().'/nexora-resume-integrity-'.bin2hex(random_bytes(6));
        mkdir($this->runDir.'/steps', 0700, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->runDir)) return;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->runDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $node) {
            $node->isDir() && !$node->isLink()
                ? rmdir($node->getPathname()) : unlink($node->getPathname());
        }
        rmdir($this->runDir);
    }

    /** @return array<string,mixed> */
    private function blockedReceipt(): array
    {
        $output = "preflight passed\n";
        $id = 'source-preflight';
        file_put_contents($this->runDir.'/steps/'.$id.'.stdout.log', $output);
        file_put_contents($this->runDir.'/steps/'.$id.'.stderr.log', '');
        return [
            'schema' => 2, 'run_id' => self::RUN, 'status' => 'blocked',
            'steps' => [[
                'id' => $id, 'required' => true, 'status' => 'pass', 'exit_code' => 0,
                'stdout_log' => 'steps/'.$id.'.stdout.log',
                'stderr_log' => 'steps/'.$id.'.stderr.log',
                'stdout_sha256' => hash('sha256', $output),
                'stderr_sha256' => hash('sha256', ''),
            ]],
        ];
    }

    #[Test]
    public function blocked_partial_run_with_intact_logs_is_readable(): void
    {
        self::assertSame([], \nexoraTargetRuntimeResumeReceiptErrors($this->blockedReceipt(), $this->runDir, self::RUN));
    }

    #[Test]
    public function tampered_stdout_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        file_put_contents($this->runDir.'/steps/source-preflight.stdout.log', 'tampered');
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function tampered_stderr_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        file_put_contents($this->runDir.'/steps/source-preflight.stderr.log', 'tampered');
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function deleted_log_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        unlink($this->runDir.'/steps/source-preflight.stderr.log');
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function changed_receipt_digest_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['steps'][0]['stdout_sha256'] = str_repeat('f', 64);
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function symlinked_step_log_blocks_reuse(): void
    {
        if (!function_exists('symlink')) self::markTestSkipped('Symlinks unsupported.');
        $receipt = $this->blockedReceipt();
        unlink($this->runDir.'/steps/source-preflight.stdout.log');
        if (!@symlink('/etc/hosts', $this->runDir.'/steps/source-preflight.stdout.log')) {
            self::markTestSkipped('Symlinks restricted.');
        }
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function noncanonical_step_log_path_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['steps'][0]['stdout_log'] = '../outside.log';
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function inconsistent_partial_step_result_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['steps'][0]['exit_code'] = 1;
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function wrong_run_id_or_schema_blocks_reuse(): void
    {
        $receipt = $this->blockedReceipt();
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, '20261016-123000-abcdef'));
        $receipt['schema'] = 1;
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function one_step_claiming_complete_pass_is_rejected(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['status'] = 'target-readiness-pass';
        $receipt['environment'] = ['full_certification_requested'=>false, 'install_dependencies_requested'=>false];
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function complete_readiness_receipt_with_valid_logs_is_accepted(): void
    {
        $ids = [
            'source-preflight', 'target-bootstrap', 'target-runtime-contract', 'inertia-contract',
            'dependency-lock-review', 'dependency-contract-strict', 'dependency-runtime',
            'frontend-typecheck', 'frontend-tests', 'frontend-build', 'frontend-build-budget',
            'package-discover', 'optimize-clear', 'artisan-about', 'route-list', 'schedule-list',
            'database-doctor', 'environment-doctor', 'filesystem-doctor', 'transfer-doctor',
            'runtime-doctor', 'concurrency-doctor', 'closure-status',
        ];
        $steps = [];
        foreach ($ids as $id) {
            $out = "PASS {$id}\n";
            file_put_contents($this->runDir.'/steps/'.$id.'.stdout.log', $out);
            file_put_contents($this->runDir.'/steps/'.$id.'.stderr.log', '');
            $steps[] = [
                'id' => $id, 'required' => $id !== 'closure-status',
                'status' => 'pass', 'exit_code' => 0,
                'stdout_log' => 'steps/'.$id.'.stdout.log',
                'stderr_log' => 'steps/'.$id.'.stderr.log',
                'stdout_sha256' => hash('sha256', $out),
                'stderr_sha256' => hash('sha256', ''),
            ];
        }
        $receipt = [
            'schema' => 2, 'run_id' => self::RUN, 'status' => 'target-readiness-pass',
            'environment' => ['full_certification_requested'=>false, 'install_dependencies_requested'=>false],
            'steps' => $steps,
        ];
        self::assertSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
        unlink($this->runDir.'/steps/frontend-build.stdout.log');
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function duplicate_step_ids_are_rejected(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['steps'][] = $receipt['steps'][0];
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }

    #[Test]
    public function unrecognized_receipt_status_is_rejected(): void
    {
        $receipt = $this->blockedReceipt();
        $receipt['status'] = 'completed';
        self::assertNotSame([], \nexoraTargetRuntimeResumeReceiptErrors($receipt, $this->runDir, self::RUN));
    }
}
