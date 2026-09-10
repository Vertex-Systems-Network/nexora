<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeRecoveryOrchestratorTest extends TestCase
{
    #[Test]
    public function orchestrator_is_dry_run_first_exact_target_bound_serialized_and_fail_closed(): void
    {
        $path = base_path('scripts/runtime-recovery-orchestrator.php');
        self::assertFileExists($path);

        $source = (string) file_get_contents($path);

        foreach ([
            "'RECOVER-RUNTIME'",
            "'1.0.0-rc.93'",
            'nexora:runtime:compatibility-status',
            'nexora:runtime:post-install-status',
            'nexora:runtime:post-install-reconcile',
            '--confirm=RECONCILE',
            'rc93-post-install-identity-repair.php',
            "'--confirm=REPAIR-RC93'",
            'receipt-refresh-required',
            "'runtime_ready'",
            "'receipt_current'",
            "'/install/source-status'",
            "'/login'",
            'nexoraRuntimeRecoveryWebIdentityProof($target, $targetAppUrl)',
            'SourceActivationIdentity',
            'SourceActivationHandshake',
            'issueCliActivation',
            'nexora:source:status',
            '--require-web-ack',
            'X-Nexora-Activation-Token',
            'X-Nexora-Source-Ack',
            'token-required',
            'acknowledged',
            "'challenge_issued'",
            "'verify_peer' => true",
            "'verify_peer_name' => true",
            "'follow_location' => 0",
            "['bypass_shell' => true]",
            '$stderrHandle = @tmpfile();',
            '2 => $stderrHandle',
            '@rewind($stderrHandle)',
            'fclose($stderrHandle)',
            'nexoraRuntimeRecoveryResolveTargetAppUrl($target)',
            'recovery-orchestrator',
            'target_verification_complete',
            'nexoraRuntimeRecoveryAppliedFailure',
            'nexoraRuntimeRecoverySetAppliedFailureContext',
            'nexoraRuntimeRecoveryAppliedFailureContext',
            "'evidence_write_status'",
            "'failure_context'",
            'LOCK_EX | LOCK_NB',
            "'.apply.lock'",
            "'exclusive-nonblocking'",
            'bin2hex(random_bytes(6))',
        ] as $required) {
            self::assertStringContainsString($required, $source);
        }

        self::assertStringContainsString("if (! \$apply)", $source);
        self::assertStringContainsString("'mutation_performed' => false", $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryCompatibility($target)', $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryPostInstallStatus($target, true)', $source);
        self::assertStringContainsString("nexoraRuntimeRecoveryNeedsReceiptRefresh(\$readiness['payload'])", $source);

        // PASS requires both the expected JSON invariants and a zero child exit.
        self::assertGreaterThanOrEqual(2, substr_count($source, "return \$result['exit_code'] === 0"));

        // Child stdout can remain a pipe, but stderr must be a transient regular
        // file. Sequentially draining two anonymous pipes can deadlock when a
        // verbose failing child fills stderr before stdout reaches EOF on Windows.
        self::assertStringNotContainsString("2 => ['pipe', 'w']", $source);
        $procClosePosition = strpos($source, '$exitCode = proc_close($process);');
        $stderrReadPosition = strpos($source, '@rewind($stderrHandle)');
        self::assertIsInt($procClosePosition);
        self::assertIsInt($stderrReadPosition);
        self::assertLessThan($stderrReadPosition, $procClosePosition);

        // Only an observed failed rc.93 result with a non-empty, fully allow-listed
        // mismatch set can enter the version-specific mutation adapter.
        self::assertStringContainsString("\$mismatches !== []", $source);
        self::assertStringContainsString("(\$payload['status'] ?? null) === 'fail'", $source);

        // Apply mode is one-writer-per-target. A second concurrent operator must
        // fail closed rather than race sealed identity or handoff receipts.
        self::assertStringContainsString('nexoraRuntimeRecoveryAcquireApplyLock($target)', $source);
        self::assertStringContainsString('LOCK_EX | LOCK_NB', $source);
        self::assertStringContainsString('Another apply-mode runtime recovery is already active for this target.', $source);

        // Once the validated apply target lock is owned, generic terminal failures
        // must inherit the live step/mutation context and write protected evidence.
        $lockStepPosition = strpos($source, "\$steps['apply_lock'] = ['status' => 'pass', 'mode' => 'exclusive-nonblocking'];");
        $failureContextPosition = strpos(
            $source,
            'nexoraRuntimeRecoverySetAppliedFailureContext(static function () use ($target, &$steps, &$mutationPerformed): array',
        );
        self::assertIsInt($lockStepPosition);
        self::assertIsInt($failureContextPosition);
        self::assertLessThan($failureContextPosition, $lockStepPosition);
        self::assertStringContainsString("'evidence_write_status' => \$receipt === null ? 'fail' : 'pass'", $source);

        // Evidence filenames are unique even for rapid sequential runs in the
        // same second, preventing silent replacement of previous receipts.
        self::assertStringContainsString('$receiptId = bin2hex(random_bytes(6));', $source);
        self::assertStringContainsString("'-'.\$receiptId.'.json'", $source);

        // app.url alone is not identity proof. A fresh target-local one-time
        // challenge must be acknowledged by that web process and verified again
        // by the exact target CLI before /login can become authoritative.
        $webProofPosition = strpos($source, "\$steps['web_identity_proof'] = \$webIdentity;");
        $loginPosition = strpos($source, "\$steps['login_smoke'] = \$loginSmoke;");
        self::assertIsInt($webProofPosition);
        self::assertIsInt($loginPosition);
        self::assertLessThan($loginPosition, $webProofPosition);
        self::assertStringContainsString("(\$webIdentity['status'] ?? 'blocked') === 'pass'", $source);
        self::assertStringContainsString('Login smoke is not authoritative until exact target-to-web identity proof passes.', $source);

        // The bearer challenge is in-process only. It is removed immediately
        // after the acknowledgement request and must never enter the public step.
        self::assertStringContainsString("unset(\$token, \$challenge['token']);", $source);
        self::assertStringNotContainsString("\$steps['web_identity_proof']['token']", $source);

        // Explicit HTTP/configuration failure remains FAIL; transport/TLS
        // unreachability remains BLOCKED and cannot be downgraded/upgraded.
        self::assertStringContainsString("'fail' => 'fail'", $source);
        self::assertStringContainsString("'fail' => 1", $source);
        self::assertStringContainsString("default => 'blocked'", $source);
        self::assertStringContainsString('if ($receipt === null)', $source);

        // The smoke target is derived only from the target application's own
        // bootstrapped app.url. Arbitrary network target overrides are forbidden.
        self::assertStringContainsString("config(\"app.url\"", $source);
        self::assertStringNotContainsString("'base-url:'", $source);
        self::assertStringNotContainsString('--base-url=', $source);

        foreach ([
            'composer install',
            'composer update',
            'npm install',
            'npm ci',
            'git pull',
            'git checkout',
            'artisan migrate',
            'migrate --force',
            'copy(',
            'shell_exec(',
            'system(',
            'passthru(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    #[Test]
    public function apply_mode_post_lock_failures_write_unique_protected_outcome_receipts(): void
    {
        self::assertTrue(function_exists('proc_open'), 'The certification runtime must expose proc_open.');

        $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-recovery-test-'.bin2hex(random_bytes(6));
        self::assertTrue(@mkdir($target.DIRECTORY_SEPARATOR.'vendor', 0700, true));
        self::assertTrue(@mkdir($target.DIRECTORY_SEPARATOR.'bootstrap', 0700, true));
        self::assertNotFalse(file_put_contents(
            $target.DIRECTORY_SEPARATOR.'artisan',
            "<?php\nfwrite(STDOUT, \"not-json\\n\");\nexit(9);\n",
        ));
        self::assertNotFalse(file_put_contents($target.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n"));
        self::assertNotFalse(file_put_contents($target.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php', "<?php\n"));

        try {
            $first = $this->runInvalidCompatibilityApply($target);
            $second = $this->runInvalidCompatibilityApply($target);

            $receipts = [];
            foreach ([$first, $second] as $result) {
                self::assertSame(1, $result['exit_code']);
                self::assertSame('', $result['stdout']);

                $payload = json_decode($result['stderr'], true, 512, JSON_THROW_ON_ERROR);
                self::assertSame('fail', $payload['status'] ?? null);
                self::assertSame('applied', $payload['mode'] ?? null);
                self::assertSame(realpath($target), $payload['target'] ?? null);
                self::assertFalse($payload['target_verification_complete'] ?? true);
                self::assertFalse($payload['mutation_performed'] ?? true);
                self::assertSame('pass', $payload['steps']['apply_lock']['status'] ?? null);
                self::assertSame('pass', $payload['evidence_write_status'] ?? null);
                self::assertSame(9, $payload['failure_context']['exit_code'] ?? null);
                self::assertSame('not-json', $payload['failure_context']['stdout'] ?? null);

                $receiptPath = $payload['evidence_receipt'] ?? null;
                self::assertIsString($receiptPath);
                self::assertFileExists($receiptPath);
                self::assertStringStartsWith(
                    realpath($target).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
                        .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator'.DIRECTORY_SEPARATOR,
                    $receiptPath,
                );

                $receipt = json_decode((string) file_get_contents($receiptPath), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame('fail', $receipt['status'] ?? null);
                self::assertSame('applied', $receipt['mode'] ?? null);
                self::assertFalse($receipt['target_verification_complete'] ?? true);
                self::assertFalse($receipt['mutation_performed'] ?? true);
                self::assertSame('pass', $receipt['steps']['apply_lock']['status'] ?? null);
                self::assertSame(9, $receipt['failure_context']['exit_code'] ?? null);
                self::assertSame('not-json', $receipt['failure_context']['stdout'] ?? null);

                $storedSeal = $receipt['receipt_sha256'] ?? null;
                self::assertIsString($storedSeal);
                unset($receipt['receipt_sha256']);
                ksort($receipt, SORT_STRING);
                self::assertSame(
                    $storedSeal,
                    hash('sha256', json_encode($receipt, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                );

                $receipts[] = $receiptPath;
            }

            self::assertNotSame($receipts[0], $receipts[1]);
            self::assertCount(2, array_values(array_filter(
                glob(
                    realpath($target).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
                        .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator'.DIRECTORY_SEPARATOR
                        .'runtime-recovery-*.json',
                ) ?: [],
                'is_file',
            )));
        } finally {
            $this->removeDirectoryTree($target);
        }
    }

    #[Test]
    public function package_json_exposes_one_operator_recovery_entrypoint(): void
    {
        $package = json_decode((string) file_get_contents(base_path('package.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            'php scripts/runtime-recovery-orchestrator.php',
            $package['scripts']['runtime:recover'] ?? null,
        );
    }

    /** @return array{exit_code:int,stdout:string,stderr:string} */
    private function runInvalidCompatibilityApply(string $target): array
    {
        $stderrHandle = tmpfile();
        self::assertIsResource($stderrHandle);
        $pipes = [];
        $process = proc_open([
            PHP_BINARY,
            base_path('scripts/runtime-recovery-orchestrator.php'),
            '--target='.$target,
            '--apply',
            '--confirm=RECOVER-RUNTIME',
        ], [
            1 => ['pipe', 'w'],
            2 => $stderrHandle,
        ], $pipes, base_path(), null, ['bypass_shell' => true]);
        self::assertIsResource($process);

        $stdout = is_resource($pipes[1] ?? null) ? trim((string) stream_get_contents($pipes[1])) : '';
        if (is_resource($pipes[1] ?? null)) {
            fclose($pipes[1]);
        }
        $exitCode = proc_close($process);

        self::assertTrue(rewind($stderrHandle));
        $stderr = trim((string) stream_get_contents($stderrHandle));
        fclose($stderrHandle);

        return [
            'exit_code' => is_int($exitCode) ? $exitCode : 1,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    private function removeDirectoryTree(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isLink() || $item->isFile()) {
                @unlink($path);
            } else {
                @rmdir($path);
            }
        }
        @rmdir($root);
    }
}
