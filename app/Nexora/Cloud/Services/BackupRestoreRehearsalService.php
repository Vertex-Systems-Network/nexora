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
        if (! ($verification['ok'] ?? false)) {
            throw new RuntimeException((string) ($verification['message'] ?? 'Backup verification failed.'));
        }

        $plan = $this->restore->create($backup);
        $recordPlan = is_array($plan['record']->plan) ? $plan['record']->plan : [];
        $steps = (array) ($recordPlan['steps'] ?? []);
        $automatic = (bool) ($recordPlan['automatic_destructive_restore'] ?? true);
        $external = (bool) ($recordPlan['requires_external_copy'] ?? false);
        $matchingSourceRuntime = (bool) ($recordPlan['requires_matching_source_runtime'] ?? true);
        $currentRuntimeExact = (bool) ($recordPlan['current_runtime_exact'] ?? false);

        return [
            'status' => $steps !== [] && ! $automatic ? 'pass' : 'fail',
            'backup_id' => $backup->id,
            'checksum' => $verification['checksum'] ?? $backup->checksum_sha256,
            'restore_plan_id' => $plan['record']->id,
            'steps' => $steps,
            'automatic_destructive_restore' => $automatic,
            'requires_external_copy' => $external,
            'requires_matching_source_runtime' => $matchingSourceRuntime,
            'current_runtime_exact' => $currentRuntimeExact,
            'backup_source_version' => $recordPlan['backup_source_version'] ?? null,
            'backup_source_generation' => $recordPlan['backup_source_generation'] ?? null,
            'backup_source_tree_sha256' => $recordPlan['backup_source_tree_sha256'] ?? null,
            'current_runtime_version' => $recordPlan['current_runtime_version'] ?? null,
            'current_runtime_generation' => $recordPlan['current_runtime_generation'] ?? null,
            'backup_storage_disk' => $recordPlan['backup_storage_disk'] ?? null,
            'backup_storage_profile_sha256' => $recordPlan['backup_storage_profile_sha256'] ?? null,
            'storage_profile_changed' => (bool) ($recordPlan['storage_profile_changed'] ?? true),
            'note' => 'This validates the artifact and guarded restore plan only. Final recovery evidence requires restoring to a disposable target with the required source-runtime identity and validating application health.',
        ];
    }
}
