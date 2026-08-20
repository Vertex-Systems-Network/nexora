<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeBackupRun;
use App\Models\RuntimeRestorePlan;
use Illuminate\Support\Str;
use RuntimeException;

final class RestorePlanner
{
    public function __construct(private BackupOrchestrator $backups, private RuntimeStorageDataPlaneIdentity $storageIdentity) {}

    /** @return array{record:RuntimeRestorePlan,confirmation:string} */
    public function create(RuntimeBackupRun $backup, ?int $createdBy = null): array
    {
        $verification = $this->backups->verify($backup);
        if (! $verification['ok']) throw new RuntimeException($verification['message']);

        $storageProfile=$this->storageIdentity->diskProfile((string)($backup->storage_disk?:'local'));$requiresExternalCopy=!(bool)($storageProfile['shared_candidate']??false);
        $confirmation = 'RESTORE-'.Str::upper(Str::random(10));
        $record = RuntimeRestorePlan::query()->create([
            'id' => (string) Str::uuid(),
            'backup_run_id' => $backup->id,
            'status' => 'planned',
            'plan' => [
                'requires_node_drain' => true,
                'requires_application_maintenance' => true,
                'requires_external_copy' => $requiresExternalCopy,
                'steps' => [
                    'Drain web and worker nodes from the load balancer.',
                    'Stop queue workers after in-flight jobs complete.',
                    'Place the application in maintenance mode.',
                    'Verify the selected backup checksum and storage identity immediately before restore.',
                    ...($requiresExternalCopy?['Copy the verified backup through an operator-controlled secure transfer to the disposable restore target; re-verify SHA-256 after transfer.']:[]),
                    'Restore the database using the driver-appropriate offline procedure.',
                    'Run migration/status and application health checks before traffic resumes.',
                    'Reactivate workers, release scheduler leadership and return nodes to active state.',
                ],
                'automatic_destructive_restore' => false,
                'backup_checksum' => $backup->checksum_sha256,
                'backup_storage_disk'=>(string)($backup->storage_disk?:'local'),
                'backup_storage_profile_sha256'=>$storageProfile['profile_sha256']??null,
                'runtime_storage_fingerprint'=>(is_array($backup->manifest)?($backup->manifest['runtime_storage_fingerprint']??null):null),
            ],
            'confirmation_hash' => hash('sha256', $confirmation),
            'created_by' => $createdBy,
            'expires_at' => now()->addHour(),
        ]);

        return ['record' => $record, 'confirmation' => $confirmation];
    }
}
