<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\InstallationRunControl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationRecoveryTest extends TestCase
{
    #[Test]
    public function stale_active_run_is_marked_interrupted_when_installer_mutex_is_free(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'rc7-'.bin2hex(random_bytes(8));
        $path = base_path('storage/app/nexora/installation-control/'.$runId.'.json');
        config()->set('installer.run_stale_seconds', 300);

        try {
            $control->start($runId, $session);
            $control->update($runId, 'migrations', false);
            $state = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $state['heartbeat_at'] = gmdate(DATE_ATOM, time() - 900);
            $state['heartbeat_epoch'] = time() - 900;
            file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);

            $summary = $control->recoverySummary($session);
            self::assertContains($runId, $summary['recovered_now']);
            self::assertSame('interrupted', $summary['interrupted']['status'] ?? null);
            self::assertFalse((bool) ($summary['interrupted']['active'] ?? true));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function failed_protected_run_can_resume_only_the_same_database_target(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'rc7-'.bin2hex(random_bytes(8));
        $path = base_path('storage/app/nexora/installation-control/'.$runId.'.json');
        $database = ['driver'=>'mysql','host'=>'127.0.0.1','port'=>3306,'database'=>'nexora_recovery','username'=>'root','password'=>'secret'];

        try {
            $control->start($runId, $session);
            $control->bindDatabaseTarget($runId, $database);
            $control->update($runId, 'migrations', false);
            $control->finish($runId, 'failed');

            self::assertNotNull($control->recoverableForDatabase($database));
            self::assertNull($control->recoverableForDatabase([...$database, 'database'=>'different_database']));
        } finally {
            @unlink($path);
        }
    }
    #[Test]
    public function interrupted_run_from_another_installer_provenance_cannot_resume(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'v50-'.bin2hex(random_bytes(8));
        $path = base_path('storage/app/nexora/installation-control/'.$runId.'.json');
        $database = ['driver'=>'mysql','host'=>'127.0.0.1','port'=>3306,'database'=>'nexora_resume_drift','username'=>'root','password'=>'secret'];

        try {
            $control->start($runId, $session);
            $control->bindDatabaseTarget($runId, $database);
            $control->update($runId, 'migrations', false);
            $control->finish($runId, 'failed');

            $state = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $state['resume_fingerprint'] = str_repeat('a', 64);
            $state['platform_version'] = '1.0.0-rc.older';
            $state['installer_protocol'] = 'v4.x';
            file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);

            $recovery = $control->recoveryForDatabase($database);
            self::assertNotNull($recovery);
            self::assertFalse((bool) ($recovery['resume_compatible'] ?? true));
            self::assertNull($control->recoverableForDatabase($database));
            self::assertStringContainsString('different Nexora installation provenance', (string) ($recovery['resume_reason'] ?? ''));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function legacy_interrupted_run_without_resume_provenance_requires_start_clean(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'v50-'.bin2hex(random_bytes(8));
        $path = base_path('storage/app/nexora/installation-control/'.$runId.'.json');
        $database = ['driver'=>'mysql','host'=>'127.0.0.1','port'=>3306,'database'=>'nexora_legacy_resume','username'=>'root','password'=>'secret'];

        try {
            $control->start($runId, $session);
            $control->bindDatabaseTarget($runId, $database);
            $control->update($runId, 'migrations', false);
            $control->finish($runId, 'failed');

            $state = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            unset($state['resume_fingerprint']);
            file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);

            $recovery = $control->recoveryForDatabase($database);
            self::assertNotNull($recovery);
            self::assertFalse((bool) ($recovery['resume_compatible'] ?? true));
            self::assertStringContainsString('predates resume-provenance protection', (string) ($recovery['resume_reason'] ?? ''));
        } finally {
            @unlink($path);
        }
    }

}
