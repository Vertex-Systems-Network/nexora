<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeUpgradeContracts(string $root): array
{
    $errors=[]; $warnings=[];
    $required=[
        'config/nexora-upgrade.php',
        'app/Nexora/Foundation/Upgrade/UpgradePlanStore.php',
        'app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php',
        'app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php',
        'app/Nexora/Foundation/Upgrade/UpgradeManager.php',
        'app/Nexora/Foundation/Upgrade/UpgradeTransactionJournal.php',
        'app/Nexora/Foundation/Upgrade/UpgradeMaintenanceLease.php',
        'app/Nexora/Foundation/Upgrade/UpgradePostHealthCheck.php',
        'app/Nexora/Foundation/Upgrade/UpgradeRecoveryDecisionStore.php',
        'app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php',
        'app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php',
        'app/Nexora/Foundation/Upgrade/UpgradeMigrationSafety.php',
        'app/Nexora/Cloud/Services/RuntimeActivityTracker.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Console/Commands/Nexora/UpgradePreflightCommand.php',
        'app/Console/Commands/Nexora/UpgradePlanCommand.php',
        'app/Console/Commands/Nexora/UpgradeApplyCommand.php',
        'app/Console/Commands/Nexora/UpgradeStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeRecoveryStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeRecoveryRecordCommand.php',
        'app/Console/Commands/Nexora/UpgradeMaintenanceLeaseCommand.php',
        'app/Console/Commands/Nexora/UpgradeLineageExportCommand.php',
        'app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeClusterLockCommand.php',
        'app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php',
        'app/Console/Commands/Nexora/UpgradeQuiescenceCommand.php',
        'app/Console/Commands/Nexora/UpgradeCutoverStatusCommand.php',
        'docs/upgrade-backup-evidence.example.json',
    ];
    foreach($required as $file){ if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="missing upgrade artifact [{$file}]"; }

    $config=is_file($root.'/config/nexora-upgrade.php')?(string)file_get_contents($root.'/config/nexora-upgrade.php'):'';
    foreach(["'supported_source'","'>=0.34 <2.0'","'require_backup'","'require_restore_readiness'","'backup_evidence_ttl_hours'","'block_preexisting_maintenance'","'maintenance_lease_path'","'recovery_decision_path'","'post_health_min_routes'","'require_migration_ledger'","'require_cluster_quiescence'","'cluster_lease_name'","'cluster_lease_seconds'","'cluster_require_shared_maintenance'","'cluster_require_runtime_quiescence'","'cluster_require_empty_queue'","'cluster_queue_names'","'block_destructive_pending_migrations'","'plan_ttl_minutes'","'lock_path'","'transaction_journal_path'","'transaction_history_path'","'transaction_stale_minutes'"] as $marker){ if(!str_contains($config,$marker))$errors[]="upgrade config missing [{$marker}]"; }

    $planStore=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradePlanStore.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradePlanStore.php'):'';
    foreach(['plan_sha256','hash_equals','AtomicFileWriter','$this->files->write'] as $marker){ if(!str_contains($planStore,$marker))$errors[]="upgrade plan integrity contract missing [{$marker}]"; }

    $state=is_file($root.'/app/Nexora/Installation/InstallationState.php')?(string)file_get_contents($root.'/app/Nexora/Installation/InstallationState.php'):'';
    foreach(['updateMetadata','installed_at','installation_id','writeSealed','_lock_sha256'] as $marker){ if(!str_contains($state,$marker))$errors[]="InstallationState upgrade metadata contract missing [{$marker}]"; }

    $themeManifest=is_file($root.'/app/Nexora/Themes/Data/ThemeManifest.php')?(string)file_get_contents($root.'/app/Nexora/Themes/Data/ThemeManifest.php'):'';
    if(!str_contains($themeManifest,"'requires' => ['nexora' => \$this->nexoraConstraint]"))$errors[]='installed theme manifests must persist requires.nexora for upgrade compatibility';

    $compat=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php'):'';
    foreach(['supported_source','pendingMigrations','extensionCompatibility','themeCompatibility','forward-only','schema_compatible_rollback','Downgrade is blocked','assessment_sha256'] as $marker){ if(!str_contains($compat,$marker))$errors[]="upgrade compatibility contract missing [{$marker}]"; }

    $backup=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php'):'';
    foreach(['BackupOrchestrator','BackupRestoreRehearsalService','backup_sha256','database_fingerprint_sha256','database_data_plane_sha256','database_schema_sha256','storage_data_plane_sha256','backup_storage_profile_sha256','restore_readiness','backup_evidence_ttl_hours','source_version','operator-name','automatic_destructive_restore'] as $marker){ if(!str_contains($backup,$marker))$errors[]="upgrade backup verifier missing [{$marker}]"; }

    $lease=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeMaintenanceLease.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeMaintenanceLease.php'):'';
    foreach(['lease_sha256','isDownForMaintenance','block_preexisting_maintenance','release'] as $marker){if(!str_contains($lease,$marker))$errors[]="upgrade maintenance ownership contract missing [{$marker}]";}

    $health=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradePostHealthCheck.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradePostHealthCheck.php'):'';
    foreach(['database_ping','route_registry','storage_writable','bootstrap_cache_writable','pre_metadata_commit','post_metadata_commit','health_sha256'] as $marker){if(!str_contains($health,$marker))$errors[]="post-upgrade health contract missing [{$marker}]";}

    $decision=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeRecoveryDecisionStore.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeRecoveryDecisionStore.php'):'';
    foreach(['restore_verified_backup','retry_pre_migration','manual_investigation','decision_sha256','automatic_recovery_executed'] as $marker){if(!str_contains($decision,$marker))$errors[]="recovery decision contract missing [{$marker}]";}

    $journal=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeTransactionJournal.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeTransactionJournal.php'):'';
    foreach(['journal_sha256','recovery_required','maintenance_required','archiveAndClear','blind down-migrations','maintenance_lease_sha256','restore_readiness_sha256','cluster_lease_sha256','migration_ledger_before_sha256','compatibility_assessment_sha256','migration_safety_sha256','abortPreMutation','No schema mutation was recorded'] as $marker){ if(!str_contains($journal,$marker))$errors[]="upgrade transaction journal missing [{$marker}]"; }

    $manager=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php'):'';
    foreach(['verified source-version backup','guarded restore-readiness','maintenanceLease->acquire','maintenanceLease->verify','post_upgrade_health_passed','post_metadata_health_passed','Artisan::call(\'down\')','Artisan::call(\'migrate\'','nexora:runtime:sync','nexora:runtime:cache','updateMetadata','trafficRestored','post-commit bookkeeping','verified backup','compatibility_assessment_sha256','migration_ledger_before','cluster_preflight','cluster->acquire','migration_ledger_converged','last_upgrade_migration_convergence_sha256','last_upgrade_migration_safety_sha256','last_upgrade_runtime_quiescence_sha256','runtime_quiesced'] as $marker){ if(!str_contains($manager,$marker))$errors[]="upgrade manager missing safety marker [{$marker}]"; }
    foreach(['migrate:rollback','migrate:reset','migrate:fresh'] as $unsafe){ if(str_contains($manager,$unsafe))$errors[]="upgrade manager must not perform automatic destructive rollback/reset [{$unsafe}]"; }

    $commands=''; foreach(glob($root.'/app/Console/Commands/Nexora/Upgrade*Command.php')?:[] as $file)$commands.=(string)file_get_contents($file)."\n";
    foreach(['stale_running','data_mutation_possible','nexora:upgrade:preflight','nexora:upgrade:plan','nexora:upgrade:apply','nexora:upgrade:status','nexora:upgrade:recovery-status','nexora:upgrade:recovery-record','nexora:upgrade:maintenance-lease','nexora:upgrade:lineage','nexora:upgrade:cluster-status','nexora:upgrade:node-status','nexora:upgrade:cluster-lock','nexora:upgrade:scheduler-lease','nexora:upgrade:quiescence','nexora:upgrade:cutover-status','--yes','--external-backup-evidence','--confirm= : Must equal RECORD'] as $marker){ if(!str_contains($commands,$marker))$errors[]="upgrade command surface missing [{$marker}]"; }

    $provider=is_file($root.'/app/Providers/NexoraServiceProvider.php')?(string)file_get_contents($root.'/app/Providers/NexoraServiceProvider.php'):'';
    foreach(['UpgradePreflightCommand::class','UpgradePlanCommand::class','UpgradeApplyCommand::class','UpgradeStatusCommand::class','UpgradeRecoveryStatusCommand::class','UpgradeRecoveryRecordCommand::class','UpgradeMaintenanceLeaseCommand::class','UpgradeLineageExportCommand::class','UpgradeClusterStatusCommand::class','UpgradeNodeStatusCommand::class','UpgradeClusterLockCommand::class','UpgradeSchedulerLeaseCommand::class','UpgradeQuiescenceCommand::class','UpgradeCutoverStatusCommand::class'] as $marker){ if(!str_contains($provider,$marker))$errors[]="NexoraServiceProvider must explicitly register upgrade command [{$marker}]"; }

    $release=is_file($root.'/scripts/build-production-release.php')?(string)file_get_contents($root.'/scripts/build-production-release.php'):'';
    foreach(["'upgrade' => [",'supported_source','verified_backup_required','automatic_database_rollback','nexora:upgrade:apply --yes','migration_ledger_required','cluster_quiescence_required','shared_maintenance_required','runtime_activity_quiescence_required','empty_queue_required','destructive_pending_migrations_blocked','quiescence_command','mixed_version_runtime_fence','cluster_status_command','node_drain_command'] as $marker){ if(!str_contains($release,$marker))$errors[]="production release manifest missing upgrade policy [{$marker}]"; }

    $releasePolicy=is_file($root.'/config/nexora-release.php')?(string)file_get_contents($root.'/config/nexora-release.php'):'';
    if(substr_count($releasePolicy,"'storage/app/nexora/upgrade/'")<2)$errors[]='production release policy must exclude and forbid runtime upgrade state';
    if(!str_contains($releasePolicy,"'config/nexora-upgrade.php'"))$errors[]='production package must retain the upgrade policy config';

    foreach(['scripts/setup-zero.bat','scripts/setup-zero.ps1','scripts/setup-zero.sh'] as $runner){
        $src=is_file($root.'/'.$runner)?(string)file_get_contents($root.'/'.$runner):'';
        if(!str_contains(str_replace('\\','/',$src),'storage/app/nexora/upgrade'))$errors[]="true-zero runner must clear upgrade state [{$runner}]";
    }
    $zero=is_file($root.'/scripts/zero-state-verify.php')?(string)file_get_contents($root.'/scripts/zero-state-verify.php'):'';
    if(!str_contains($zero,"'storage/app/nexora/upgrade'"))$errors[]='zero-state verifier must reject persisted upgrade state';

    $evidence=is_file($root.'/docs/upgrade-backup-evidence.example.json')?(string)file_get_contents($root.'/docs/upgrade-backup-evidence.example.json'):'';
    foreach(['"schema": 4','"status": "fail"','operator-name','database_fingerprint_sha256','database_data_plane_sha256','database_schema_sha256','storage_data_plane_sha256','backup_storage_profile_sha256','restore_readiness','automatic_destructive_restore'] as $marker){if(!str_contains($evidence,$marker))$errors[]="upgrade backup evidence example missing fail-closed marker [{$marker}]";}

    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['commands'=>14,'compatibility_domains'=>4,'backup_modes'=>2,'restore_readiness_gate'=>1,'maintenance_ownership'=>1,'post_upgrade_health'=>2,'recovery_decision_record'=>1,'transaction_journal'=>1,'distributed_upgrade_lease'=>1,'migration_ledger'=>1,'automatic_peer_drain'=>0,'automatic_database_rollback'=>0]];
}
