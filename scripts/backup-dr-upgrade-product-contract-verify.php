<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    'backup' => $root.'/app/Nexora/Cloud/Services/BackupOrchestrator.php',
    'compatibility' => $root.'/app/Nexora/Cloud/Services/BackupRecoveryCompatibility.php',
    'planner' => $root.'/app/Nexora/Cloud/Services/RestorePlanner.php',
    'rehearsal' => $root.'/app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php',
    'evidence' => $root.'/scripts/backup-restore-evidence-verify.php',
    'final_evidence' => $root.'/scripts/lib/final-evidence.php',
    'test' => $root.'/tests/Feature/Cloud/BackupRecoveryIdentityTest.php',
];

$failures = [];
$files = [];
foreach ($paths as $key => $path) {
    if (! is_file($path)) {
        $failures[] = "Missing Backup/DR source file [{$key}].";
        $files[$key] = '';
        continue;
    }

    $content = file_get_contents($path);
    $files[$key] = is_string($content) ? $content : '';
    if ($files[$key] === '') {
        $failures[] = "Unable to read Backup/DR source file [{$key}].";
    }
}

$require = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (! str_contains($files[$key] ?? '', $needle)) {
        $failures[] = $message;
    }
};
$forbid = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (str_contains($files[$key] ?? '', $needle)) {
        $failures[] = $message;
    }
};

// Recovery-ready backups must seal artifact + runtime provenance before success is recorded.
$require('backup', "'platform_version' => $platformVersion", 'Runtime backups must seal the source platform version.');
$require('backup', "'deployment_generation' => $deploymentGeneration", 'Runtime backups must seal the source deployment generation.');
$require('backup', "'source_tree_sha256' => $sourceTreeSha256", 'Runtime backups must seal the source-tree SHA-256.');
$require('backup', "'artifact_checksum_sha256' => $sourceChecksum", 'Runtime backups must bind the manifest to the persisted artifact checksum.');
$require('backup', 'cannot create a recovery-ready backup without a complete deployment identity', 'Backup creation must fail closed when deployment identity is incomplete.');
$require('backup', "'stream_verified' => true", 'Runtime backup completion must record streaming verification.');
$require('backup', 'Backup streaming verification failed. Review server logs and protected storage health.', 'Backup verification failure must remain operator-safe and generic.');
$forbid('backup', "'message' => $e->getMessage()", 'Backup verification must not disclose raw exception messages to callers.');

// Recovery compatibility must reject ambiguous/legacy/tampered identity rather than guessing.
$require('compatibility', "!== 'nexora-runtime-backup-v1'", 'Recovery compatibility must require the supported backup manifest format.');
$require('compatibility', "preg_match('/^[a-f0-9]{64}$/', $hash)", 'Recovery compatibility must validate sealed SHA-256 identities.');
$require('compatibility', 'database driver does not match the backup record', 'Recovery compatibility must bind the manifest to the backup driver.');
$require('compatibility', 'storage disk does not match the backup record', 'Recovery compatibility must bind the manifest to the backup storage disk.');
$require('compatibility', 'checksum identity does not match the verified backup record', 'Recovery compatibility must bind the manifest checksum to the backup record.');
$require('compatibility', "'requires_matching_source_runtime' => ! $exact", 'Recovery compatibility must explicitly fence cross-generation restore planning.');

// Restore planning stays non-destructive and source-runtime aware.
$require('planner', '$this->compatibility->assess($backup)', 'Restore planning must assess backup recovery identity after artifact verification.');
$require('planner', "'requires_matching_source_runtime' => $recovery['requires_matching_source_runtime']", 'Restore plan must persist source-runtime compatibility state.');
$require('planner', "'backup_source_version' => $recovery['source_version']", 'Restore plan must persist the backup source version.');
$require('planner', "'backup_source_generation' => $recovery['source_generation']", 'Restore plan must persist the backup source generation.');
$require('planner', "'backup_source_tree_sha256' => $recovery['source_tree_sha256']", 'Restore plan must persist the backup source-tree identity.');
$require('planner', "'automatic_destructive_restore' => false", 'Restore plans must never enable automatic destructive restore.');
$require('planner', 'disposable recovery target', 'Restore plan must require an isolated/disposable recovery target.');
$require('planner', 'Provision an isolated recovery runtime matching Nexora', 'Cross-generation restore plans must require a matching source runtime.');

// Rehearsal output must make recovery identity actionable without pretending target recovery happened.
$require('rehearsal', "'requires_matching_source_runtime' => $matchingSourceRuntime", 'Backup rehearsal output must expose source-runtime compatibility.');
$require('rehearsal', "'backup_source_generation' => $recordPlan['backup_source_generation'] ?? null", 'Backup rehearsal output must expose the sealed source generation.');
$require('rehearsal', "'current_runtime_generation' => $recordPlan['current_runtime_generation'] ?? null", 'Backup rehearsal output must expose current runtime generation for comparison.');
$require('rehearsal', 'Final recovery evidence requires restoring to a disposable target', 'Source rehearsal must not claim target recovery certification.');

// Final evidence remains a separately supplied real disposable-target rehearsal.
$require('evidence', 'record a real disposable-target rehearsal', 'Backup/restore final evidence must require a real disposable-target rehearsal.');
$require('final_evidence', "'backup_verified'", 'Upgrade rehearsal evidence must require a verified backup.');
$require('final_evidence', "'restore_readiness_verified'", 'Upgrade rehearsal evidence must require restore readiness.');
$require('final_evidence', "'recovery_status_drill'", 'Upgrade rehearsal evidence must include a recovery status drill.');
$require('final_evidence', "'migration_safety_plan_bound'", 'Upgrade rehearsal evidence must bind migration safety to the recovery plan.');

foreach ([
    'test_restore_plan_accepts_exact_recovery_identity_and_remains_non_destructive',
    'test_restore_plan_requires_matching_source_runtime_when_generation_differs',
    'test_restore_plan_rejects_legacy_backup_without_recovery_identity',
    'test_restore_plan_rejects_manifest_checksum_identity_mismatch',
] as $method) {
    $require('test', $method, 'Missing N1.25 backup/DR regression: '.$method);
}

if ($failures !== []) {
    fwrite(STDERR, "Nexora Backup / DR / Upgrade Product Contract: FAIL\n");
    foreach (array_values(array_unique($failures)) as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Nexora Backup / DR / Upgrade Product Contract: PASS\n");
fwrite(STDOUT, " - recovery-ready backups seal release/generation/tree/artifact identity\n");
fwrite(STDOUT, " - legacy, ambiguous and checksum-mismatched recovery identity fails closed\n");
fwrite(STDOUT, " - restore plans are source-runtime aware and never automatically destructive\n");
fwrite(STDOUT, " - final recovery certification remains bound to a real disposable-target rehearsal\n");
