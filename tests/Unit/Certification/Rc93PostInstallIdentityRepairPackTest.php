<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Rc93PostInstallIdentityRepairPackTest extends TestCase
{
    #[Test]
    public function repair_pack_is_version_pinned_dry_run_first_and_fail_closed(): void
    {
        $path = base_path('scripts/rc93-post-install-identity-repair.php');
        self::assertFileExists($path);

        $source = (string) file_get_contents($path);

        foreach ([
            "'1.0.0-rc.93'",
            "'REPAIR-RC93'",
            "'environment'",
            "'activation'",
            "'service'",
            "'process'",
            "['target:', 'apply', 'confirm:', 'help']",
            'SourceActivationIdentity',
            'RuntimeDeploymentIdentity',
            'RuntimeVersionGuard',
            'AtomicFileWriter',
            "['inspect', 'metadata', 'lockPath', 'updateMetadata']",
            "'runtime_environment_fingerprint'",
            "'runtime_activation_fingerprint'",
            "'runtime_service_fingerprint'",
            "'runtime_process_fingerprint'",
            "'installed-data-plane'",
            'rollback',
            'repair-backups',
            'repair-receipts',
            'target_verification_still_required',
        ] as $required) {
            self::assertStringContainsString($required, $source);
        }

        self::assertStringContainsString('if (! $apply)', $source);
        self::assertStringContainsString("'mutation_performed' => false", $source);
        self::assertStringContainsString('$installation->updateMetadata($updates)', $source);
        self::assertStringContainsString('$atomic->write($lockPath, $lockBytes', $source);

        foreach ([
            'composer update',
            'composer install',
            'npm install',
            'npm ci',
            'copy(',
            'shell_exec(',
            'exec(',
            'system(',
            'passthru(',
            'proc_open(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    #[Test]
    public function powershell_wrapper_preserves_explicit_apply_confirmation(): void
    {
        $path = base_path('scripts/rc93-post-install-identity-repair.ps1');
        self::assertFileExists($path);

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('[Parameter(Mandatory = $true)]', $source);
        self::assertStringContainsString("if (\$Confirm -ne 'REPAIR-RC93')", $source);
        self::assertStringContainsString("\$arguments += '--apply'", $source);
        self::assertStringContainsString("\$arguments += '--confirm=REPAIR-RC93'", $source);
    }
}
