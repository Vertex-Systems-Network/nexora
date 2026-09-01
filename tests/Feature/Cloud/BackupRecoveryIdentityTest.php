<?php

declare(strict_types=1);

namespace Tests\Feature\Cloud;

use App\Models\RuntimeBackupRun;
use App\Nexora\Cloud\Services\RestorePlanner;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class BackupRecoveryIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'filesystems.default' => 'local',
            'nexora-storage-runtime.backup_disk' => 'local',
        ]);
        Storage::fake('local');
    }

    public function test_restore_plan_accepts_exact_recovery_identity_and_remains_non_destructive(): void
    {
        $backup = $this->makeBackup();

        $result = app(RestorePlanner::class)->create($backup);
        $plan = (array) $result['record']->plan;

        self::assertFalse((bool) $plan['requires_matching_source_runtime']);
        self::assertTrue((bool) $plan['current_runtime_exact']);
        self::assertFalse((bool) $plan['automatic_destructive_restore']);
        self::assertSame($backup->checksum_sha256, $plan['backup_checksum']);
        self::assertSame($backup->manifest['platform_version'], $plan['backup_source_version']);
        self::assertSame($backup->manifest['deployment_generation'], $plan['backup_source_generation']);
        self::assertSame($backup->manifest['source_tree_sha256'], $plan['backup_source_tree_sha256']);
        self::assertNotEmpty($plan['steps']);
    }

    public function test_restore_plan_requires_matching_source_runtime_when_generation_differs(): void
    {
        $current = app(RuntimeDeploymentIdentity::class)->current();
        $differentGeneration = hash('sha256', 'n1.25-recovery-generation:'.(string) ($current['generation'] ?? ''));
        $backup = $this->makeBackup(['deployment_generation' => $differentGeneration]);

        $result = app(RestorePlanner::class)->create($backup);
        $plan = (array) $result['record']->plan;

        self::assertTrue((bool) $plan['requires_matching_source_runtime']);
        self::assertFalse((bool) $plan['current_runtime_exact']);
        self::assertSame($differentGeneration, $plan['backup_source_generation']);
        self::assertStringContainsString(
            'Provision an isolated recovery runtime matching Nexora',
            implode("\n", (array) $plan['steps']),
        );
    }

    public function test_restore_plan_rejects_legacy_backup_without_recovery_identity(): void
    {
        $backup = $this->makeBackup();
        $manifest = (array) $backup->manifest;
        unset($manifest['deployment_generation']);
        $backup->forceFill(['manifest' => $manifest])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup recovery manifest has an invalid deployment generation identity.');

        app(RestorePlanner::class)->create($backup->refresh());
    }

    public function test_restore_plan_rejects_manifest_checksum_identity_mismatch(): void
    {
        $backup = $this->makeBackup(['artifact_checksum_sha256' => str_repeat('a', 64)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup recovery manifest checksum identity does not match the verified backup record.');

        app(RestorePlanner::class)->create($backup);
    }

    /** @param array<string,mixed> $manifestOverrides */
    private function makeBackup(array $manifestOverrides = []): RuntimeBackupRun
    {
        $payload = 'nexora-n1.25-recovery-artifact:'.bin2hex(random_bytes(16));
        $checksum = hash('sha256', $payload);
        $path = 'nexora/runtime-backups/n1-25/database.sqlite';
        Storage::disk('local')->put($path, $payload);

        $deployment = app(RuntimeDeploymentIdentity::class)->current();
        $profile = app(RuntimeStorageDataPlaneIdentity::class)->diskProfile('local');
        $manifest = array_merge([
            'format' => 'nexora-runtime-backup-v1',
            'platform_version' => (string) ($deployment['platform_version'] ?? config('nexora.version')),
            'deployment_generation' => (string) ($deployment['generation'] ?? ''),
            'source_tree_sha256' => (string) ($deployment['source_tree_sha256'] ?? ''),
            'artifact_checksum_sha256' => $checksum,
            'database_driver' => 'sqlite',
            'backup_storage_disk' => 'local',
            'backup_storage_profile_sha256' => $profile['profile_sha256'] ?? null,
            'runtime_storage_fingerprint' => hash('sha256', 'test-storage-fingerprint'),
        ], $manifestOverrides);

        return RuntimeBackupRun::query()->create([
            'type' => 'database',
            'status' => 'completed',
            'driver' => 'sqlite',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'checksum_sha256' => $checksum,
            'bytes' => strlen($payload),
            'manifest' => $manifest,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
