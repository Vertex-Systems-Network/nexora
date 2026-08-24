<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeRecoveryOrchestratorTest extends TestCase
{
    #[Test]
    public function orchestrator_is_dry_run_first_target_bound_serialized_and_fail_closed(): void
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
            "'/login'",
            "'verify_peer' => true",
            "'verify_peer_name' => true",
            "'follow_location' => 0",
            "['bypass_shell' => true]",
            'nexoraRuntimeRecoveryResolveTargetAppUrl($target)',
            'target-owned /login',
            'recovery-orchestrator',
            'target_verification_complete',
            'nexoraRuntimeRecoveryAppliedFailure',
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

        // Only an observed failed rc.93 result with a non-empty, fully allow-listed
        // mismatch set can enter the version-specific mutation adapter.
        self::assertStringContainsString("\$mismatches !== []", $source);
        self::assertStringContainsString("(\$payload['status'] ?? null) === 'fail'", $source);

        // Apply mode is one-writer-per-target. A second concurrent operator must
        // fail closed rather than race sealed identity or handoff receipts.
        self::assertStringContainsString('nexoraRuntimeRecoveryAcquireApplyLock($target)', $source);
        self::assertStringContainsString('LOCK_EX | LOCK_NB', $source);
        self::assertStringContainsString('Another apply-mode runtime recovery is already active for this target.', $source);

        // Evidence filenames are unique even for rapid sequential runs in the
        // same second, preventing silent replacement of previous receipts.
        self::assertStringContainsString('$receiptId = bin2hex(random_bytes(6));', $source);
        self::assertStringContainsString("'-'.\$receiptId.'.json'", $source);

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
    public function package_json_exposes_one_operator_recovery_entrypoint(): void
    {
        $package = json_decode((string) file_get_contents(base_path('package.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            'php scripts/runtime-recovery-orchestrator.php',
            $package['scripts']['runtime:recover'] ?? null,
        );
    }
}
