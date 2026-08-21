<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeBackupRun;
use RuntimeException;

final readonly class BackupRecoveryCompatibility
{
    public function __construct(private RuntimeDeploymentIdentity $deployment) {}

    /**
     * @return array{
     *   source_version:string,
     *   source_generation:string,
     *   source_tree_sha256:string,
     *   current_version:string,
     *   current_generation:string,
     *   current_runtime_exact:bool,
     *   requires_matching_source_runtime:bool
     * }
     */
    public function assess(RuntimeBackupRun $backup): array
    {
        $manifest = is_array($backup->manifest) ? $backup->manifest : [];
        if (($manifest['format'] ?? null) !== 'nexora-runtime-backup-v1') {
            throw new RuntimeException('Backup recovery manifest format is missing or unsupported.');
        }

        $sourceVersion = trim((string) ($manifest['platform_version'] ?? ''));
        $sourceGeneration = strtolower(trim((string) ($manifest['deployment_generation'] ?? '')));
        $sourceTree = strtolower(trim((string) ($manifest['source_tree_sha256'] ?? '')));
        $manifestDriver = trim((string) ($manifest['database_driver'] ?? ''));
        $manifestDisk = trim((string) ($manifest['backup_storage_disk'] ?? ''));
        $manifestChecksum = strtolower(trim((string) ($manifest['artifact_checksum_sha256'] ?? '')));

        if ($sourceVersion === '') {
            throw new RuntimeException('Backup recovery manifest is missing its source platform version.');
        }
        foreach (['deployment generation' => $sourceGeneration, 'source tree' => $sourceTree, 'artifact checksum' => $manifestChecksum] as $label => $hash) {
            if (preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                throw new RuntimeException('Backup recovery manifest has an invalid '.$label.' identity.');
            }
        }
        if ($manifestDriver === '' || ! hash_equals($manifestDriver, (string) $backup->driver)) {
            throw new RuntimeException('Backup recovery manifest database driver does not match the backup record.');
        }
        $recordDisk = (string) ($backup->storage_disk ?: 'local');
        if ($manifestDisk === '' || ! hash_equals($manifestDisk, $recordDisk)) {
            throw new RuntimeException('Backup recovery manifest storage disk does not match the backup record.');
        }
        $recordChecksum = strtolower(trim((string) $backup->checksum_sha256));
        if (preg_match('/^[a-f0-9]{64}$/', $recordChecksum) !== 1 || ! hash_equals($recordChecksum, $manifestChecksum)) {
            throw new RuntimeException('Backup recovery manifest checksum identity does not match the verified backup record.');
        }

        $current = $this->deployment->current();
        $currentVersion = trim((string) ($current['platform_version'] ?? config('nexora.version', '')));
        $currentGeneration = strtolower(trim((string) ($current['generation'] ?? '')));
        $exact = $currentVersion !== ''
            && preg_match('/^[a-f0-9]{64}$/', $currentGeneration) === 1
            && hash_equals($sourceVersion, $currentVersion)
            && hash_equals($sourceGeneration, $currentGeneration);

        return [
            'source_version' => $sourceVersion,
            'source_generation' => $sourceGeneration,
            'source_tree_sha256' => $sourceTree,
            'current_version' => $currentVersion,
            'current_generation' => $currentGeneration,
            'current_runtime_exact' => $exact,
            'requires_matching_source_runtime' => ! $exact,
        ];
    }
}
