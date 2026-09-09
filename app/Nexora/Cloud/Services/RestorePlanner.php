<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeBackupRun;
use App\Models\RuntimeRestorePlan;
use Illuminate\Support\Str;
use RuntimeException;

final class RestorePlanner
{
    public function __construct(
        private BackupOrchestrator $backups,
        private RuntimeStorageDataPlaneIdentity $storageIdentity,
        private BackupRecoveryCompatibility $compatibility,
    ) {}

    /** @return array{record:RuntimeRestorePlan,confirmation:string} */
    public function create(RuntimeBackupRun $backup, ?int $createdBy = null): array
    {
        $verification = $this->backups->verify($backup);
        if (! $verification['ok']) {
            throw new RuntimeException($verification['message']);
        }

        // Recovery identity is mandatory. Legacy/ambiguous backup rows may still
        // be downloaded and inspected, but Nexora will not emit an executable
        // recovery plan when it cannot prove which runtime produced the backup.
        $recovery = $this->compatibility->assess($backup);
        $manifest = is_array($backup->manifest) ? $backup->manifest : [];

        $storageProfile = $this->storageIdentity->diskProfile((string) ($backup->storage_disk ?: 'local'));
        $requiresExternalCopy = ! (bool) ($storageProfile['shared_candidate'] ?? false);
        $recordedStorageProfile = strtolower(trim((string) ($manifest['backup_storage_profile_sha256'] ?? '')));
        $currentStorageProfile = strtolower(trim((string) ($storageProfile['profile_sha256'] ?? '')));
        $storageProfileChanged = preg_match('/^[a-f0-9]{64}$/', $recordedStorageProfile) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $currentStorageProfile) !== 1
            || ! hash_equals($recordedStorageProfile, $currentStorageProfile);

        $steps = [
            'Drain web and worker nodes from the load balancer.',
            'Stop queue workers after in-flight jobs complete.',
            'Place the application in maintenance mode.',
            'Verify the selected backup checksum, recovery identity and storage identity immediately before restore.',
        ];
        if ($recovery['requires_matching_source_runtime']) {
            $steps[] = 'Provision an isolated recovery runtime matching Nexora '.$recovery['source_version'].' and deployment generation '.$recovery['source_generation'].' before applying the backup; do not restore this artifact directly into the current runtime.';
        }
        if ($storageProfileChanged) {
            $steps[] = 'Re-validate the backup storage profile and operator-controlled transfer path because the current storage identity differs from the profile recorded at backup time.';
        }
        if ($requiresExternalCopy) {
            $steps[] = 'Copy the verified backup through an operator-controlled secure transfer to the disposable restore target; re-verify SHA-256 after transfer.';
        }
        $steps[] = 'Restore the database using the driver-appropriate offline procedure on a disposable recovery target.';
        $steps[] = 'Run migration/status and application health checks before traffic resumes.';
        $steps[] = 'Reactivate workers, release scheduler leadership and return nodes to active state only after recovery evidence passes.';

        $confirmation = 'RESTORE-'.Str::upper(Str::random(10));
        $record = RuntimeRestorePlan::query()->create([
            'id' => (string) Str::uuid(),
            'backup_run_id' => $backup->id,
            'status' => 'planned',
            'plan' => [
                'requires_node_drain' => true,
                'requires_application_maintenance' => true,
                'requires_external_copy' => $requiresExternalCopy,
                'requires_matching_source_runtime' => $recovery['requires_matching_source_runtime'],
                'current_runtime_exact' => $recovery['current_runtime_exact'],
                'storage_profile_changed' => $storageProfileChanged,
                'backup_source_version' => $recovery['source_version'],
                'backup_source_generation' => $recovery['source_generation'],
                'backup_source_tree_sha256' => $recovery['source_tree_sha256'],
                'current_runtime_version' => $recovery['current_version'],
                'current_runtime_generation' => $recovery['current_generation'],
                'steps' => $steps,
                'automatic_destructive_restore' => false,
                'backup_checksum' => $backup->checksum_sha256,
                'backup_storage_disk' => (string) ($backup->storage_disk ?: 'local'),
                'backup_storage_profile_sha256' => $storageProfile['profile_sha256'] ?? null,
                'backup_recorded_storage_profile_sha256' => $recordedStorageProfile !== '' ? $recordedStorageProfile : null,
                'runtime_storage_fingerprint' => $manifest['runtime_storage_fingerprint'] ?? null,
            ],
            'confirmation_hash' => hash('sha256', $confirmation),
            'created_by' => $createdBy,
            'expires_at' => now()->addHour(),
        ]);

        return ['record' => $record, 'confirmation' => $confirmation];
    }
}
