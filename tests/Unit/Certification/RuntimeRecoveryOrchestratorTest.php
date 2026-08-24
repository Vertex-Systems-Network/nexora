<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeRecoveryOrchestratorTest extends TestCase
{
    #[Test]
    public function orchestrator_is_dry_run_first_bounded_and_independently_verifies_recovery(): void
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
            'recovery-orchestrator',
            'target_verification_complete',
            "['bypass_shell' => true]",
        ] as $required) {
            self::assertStringContainsString($required, $source);
        }

        self::assertStringContainsString("if (! \$apply)", $source);
        self::assertStringContainsString("'mutation_performed' => false", $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryCompatibility($target)', $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryPostInstallStatus($target, true)', $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryNeedsReceiptRefresh($readinessPayload)', $source);

        // A JSON PASS is not trusted unless the child command itself exited 0.
        self::assertStringContainsString("return \$result['exit_code'] === 0", $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryCompatibilityPayloadPass($result[\'payload\'])', $source);
        self::assertStringContainsString('nexoraRuntimeRecoveryReadyPayload($result[\'payload\'])', $source);

        // Only an observed failed rc.93 compatibility result with a non-empty,
        // fully allow-listed mismatch set can enter the version-specific adapter.
        self::assertStringContainsString("\$mismatches !== []", $source);
        self::assertStringContainsString("(\$compatibilityPayload['status'] ?? null) === 'fail'", $source);

        // Explicit HTTP failures stay FAIL; transport/TLS unreachability is BLOCKED.
        self::assertStringContainsString("'fail' => 'fail'", $source);
        self::assertStringContainsString("'fail' => 1", $source);
        self::assertStringContainsString("default => 'blocked'", $source);
        self::assertStringContainsString('if ($receipt === null)', $source);

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
