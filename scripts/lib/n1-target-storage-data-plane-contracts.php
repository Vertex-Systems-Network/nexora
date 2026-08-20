<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeStorageDataPlaneContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-storage-runtime.php',
        'app/Nexora/Cloud/Services/RuntimeStorageDataPlaneIdentity.php',
        'app/Console/Commands/Nexora/RuntimeStorageStatusCommand.php',
        'tests/Architecture/N100V38StorageDataPlaneArchitectureTest.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.8 storage data-plane artifact missing [{$file}]";
    $config=$read($root.'/config/nexora-storage-runtime.php');foreach(['require_exact_data_plane','require_shared_for_ha','require_backup_shared_for_ha','media_disk','backup_disk','object_disk','cluster_id','deep_probe_prefix','queue_payload_schema','NEXORA_QUEUE_PAYLOAD_SCHEMA'] as $m)if(!str_contains($config,$m))$errors[]="storage runtime config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeStorageDataPlaneIdentity.php');foreach(['diskProfile','bucket_sha256','endpoint_sha256','locator_sha256','shared_candidate','deepInspect','write/read/delete probe passed','public/storage','profile_sha256'] as $m)if(!str_contains($identity,$m))$errors[]="storage data-plane identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['current_runtime_storage_fingerprint','installed_runtime_storage_fingerprint','runtime_storage_compatible','max(13','runtime_storage_fingerprint','different Nexora persistent storage data-plane fingerprint'] as $m)if(!str_contains($guard,$m))$errors[]="runtime storage fence missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['RuntimeStorageDataPlaneIdentity::class',"'payload_schema'=>max(13",'runtime_storage_fingerprint'] as $m)if(!str_contains($provider,$m))$errors[]="queue storage data-plane fence missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_storage_fingerprint','object_storage_driver','media_storage_disk','backup_storage_disk'] as $m)if(!str_contains($node,$m))$errors[]="node storage advertisement missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['local_storage_data_plane','shared_backup_storage','runtime_storage_data_plane_consistency'] as $m)if(!str_contains($ha,$m))$errors[]="HA storage convergence missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(['RuntimeStorageDataPlaneIdentity','runtime_storage_fingerprint','object_storage_disk','media_storage_disk','backup_storage_disk','storage_deep_probe_sha256','prepareLocalMediaPublicLink','storage:link','refuses to overwrite it automatically'] as $m)if(!str_contains($installer,$m))$errors[]="installer storage lineage missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_storage_data_plane','Persistent storage data-plane fingerprint changed','storage_data_plane_attested','last_upgrade_storage_fingerprint'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade storage binding missing [{$m}]";
    $restore=$read($root.'/app/Nexora/Cloud/Services/RestorePlanner.php').$read($root.'/app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php');foreach(['requires_external_copy','shared_candidate','secure transfer','re-verify SHA-256','backup_storage_profile_sha256'] as $m)if(!str_contains($restore,$m))$errors[]="restore storage locality boundary missing [{$m}]";
    $backup=$read($root.'/app/Nexora/Cloud/Services/BackupOrchestrator.php').$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeBackupVerifier.php');foreach(['NEXORA_BACKUP_STORAGE_DISK','backup_storage_profile_sha256','runtime_storage_fingerprint','schema 4 PASS','storage_data_plane_sha256'] as $m)if(!str_contains($backup,$m)&&!str_contains($config,$m))$errors[]="backup storage binding missing [{$m}]";
    $media=$read($root.'/app/Nexora/Media/Services/MediaManager.php');foreach(["config('nexora-storage-runtime.media_disk'",'Storage::disk($mediaDisk)',"'disk'=>\$mediaDisk"] as $m)if(!str_contains($media,$m))$errors[]="media storage policy missing [{$m}]";
    $deployment=$read($root.'/scripts/lib/deployment-generation.php').$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['storage_policy_sha256','config/nexora-storage-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment generation storage-policy binding missing [{$m}]";
    $builder=$read($root.'/scripts/build-production-release.php');foreach(['storagePolicyHash','storage_policy_sha256','storage_data_plane_contract','shared_storage_required_for_ha','backup_storage_binding_required','nexora:runtime:storage-status --deep --assert-installed'] as $m)if(!str_contains($builder,$m))$errors[]="production release storage contract missing [{$m}]";
    $c2=$read($root.'/scripts/n1-c2-laravel-runtime-certify.php');if(!str_contains($c2,"'runtime-storage-status'"))$errors[]='C2 deep storage data-plane gate missing';
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');if(!str_contains($middleware,'X-Nexora-Storage-Fingerprint'))$errors[]='web storage fingerprint response header missing';
    $env=$read($root.'/.env.production.example');foreach(['NEXORA_QUEUE_PAYLOAD_SCHEMA=13','NEXORA_STORAGE_REQUIRE_EXACT_DATA_PLANE=true','NEXORA_BACKUP_STORAGE_REQUIRE_SHARED_FOR_HA=true','NEXORA_MEDIA_STORAGE_DISK=public','NEXORA_BACKUP_STORAGE_DISK='] as $m)if(!str_contains($env,$m))$errors[]="production storage runtime default missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['storage_data_plane_identity'=>1,'deep_roundtrip_roles'=>3,'queue_payload_schema'=>13,'c2_storage_gate'=>1,'backup_storage_binding'=>1,'restore_locality_guard'=>1,'installer_public_link_guard'=>1,'ha_storage_checks'=>2,'automatic_storage_migration'=>0]];
}
