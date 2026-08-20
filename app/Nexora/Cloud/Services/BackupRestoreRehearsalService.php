<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeBackupRun;
use RuntimeException;

final class BackupRestoreRehearsalService
{
    public function __construct(private BackupOrchestrator $backups, private RestorePlanner $restore) {}

    /** @return array<string,mixed> */
    public function validate(RuntimeBackupRun $backup): array
    {
        $verification = $this->backups->verify($backup);
        if (! ($verification['ok'] ?? false)) throw new RuntimeException((string) ($verification['message'] ?? 'Backup verification failed.'));
        $plan = $this->restore->create($backup);
        $steps = (array) ($plan['record']->plan['steps'] ?? []);
        $automatic = (bool) ($plan['record']->plan['automatic_destructive_restore'] ?? true);$external=(bool)($plan['record']->plan['requires_external_copy']??false);
        return [
            'status' => $steps !== [] && ! $automatic ? 'pass' : 'fail',
            'backup_id' => $backup->id,
            'checksum' => $verification['checksum'] ?? $backup->checksum_sha256,
            'restore_plan_id' => $plan['record']->id,
            'steps' => $steps,
            'automatic_destructive_restore' => $automatic,
            'requires_external_copy'=>$external,
            'backup_storage_disk'=>$plan['record']->plan['backup_storage_disk']??null,
            'backup_storage_profile_sha256'=>$plan['record']->plan['backup_storage_profile_sha256']??null,
            'note' => 'This validates the artifact and guarded restore plan only. Final recovery evidence requires restoring to a disposable target and validating application health.',
        ];
    }
}
