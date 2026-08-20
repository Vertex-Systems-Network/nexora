<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeResourceEnvelopeContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-resource-runtime.php',
        'app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php',
        'app/Console/Commands/Nexora/RuntimeResourceStatusCommand.php',
        'scripts/lib/n1-target-resource-envelope-contracts.php',
        'scripts/n1-target-resource-envelope-contract-verify.php',
    ];
    foreach($required as $f)if(!is_file($root.'/'.$f)||filesize($root.'/'.$f)===0)$errors[]="resource envelope artifact missing [{$f}]";
    $config=$read($root.'/config/nexora-resource-runtime.php');foreach(['require_exact_resource_policy','require_deep_capacity_for_upgrade','require_deep_capacity_for_ha','minimum_memory_headroom_bytes','minimum_queue_memory_headroom_bytes','minimum_temp_free_bytes','minimum_storage_free_bytes','minimum_transfer_free_bytes','minimum_bootstrap_free_bytes','minimum_backup_staging_free_bytes','minimum_open_files_soft','queue_payload_schema'] as $m)if(!str_contains($config,$m))$errors[]="resource policy missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeResourceEnvelopeIdentity.php');foreach(['RuntimeLimitsDoctor','memory_get_usage','disk_free_space','posix_getrlimit','/proc/self/limits','assertUpgradeCapacity','assertBackupScratchCapacity','queue_memory_headroom','backup_staging_free_space','minimum_backup_staging_free_bytes','resource_policy_sha256','deep_sha256'] as $m)if(!str_contains($identity,$m))$errors[]="resource envelope identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['max(13','runtime_resource_fingerprint','different Nexora runtime resource policy envelope','runtime_resource_compatible'] as $m)if(!str_contains($guard,$m))$errors[]="runtime resource queue/install fence missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['RuntimeResourceEnvelopeIdentity','runtime_resource_fingerprint','max(13'] as $m)if(!str_contains($provider,$m))$errors[]="queue resource metadata/provider binding missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_resource_fingerprint','runtime_resource_status','resource_deep_probe_sha256','resource_backup_staging_free_bytes','resource_worker_restart_bytes'] as $m)if(!str_contains($node,$m))$errors[]="runtime node resource metadata missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['local_resource_envelope','runtime_resource_policy_consistency','runtime_resource_capacity_minimums','require_deep_capacity_for_ha'] as $m)if(!str_contains($ha,$m))$errors[]="HA resource convergence missing [{$m}]";
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');if(!str_contains($middleware,'X-Nexora-Resource-Fingerprint'))$errors[]='runtime web responses must expose non-secret resource-policy fingerprint';
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_resource_envelope','resource_envelope_attested','runtime_resource_fingerprint','resource_deep_probe_sha256','require_deep_capacity_for_upgrade'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade resource admission/binding missing [{$m}]";
    $backup=$read($root.'/app/Nexora/Cloud/Services/BackupOrchestrator.php');foreach(['assertBackupScratchCapacity','runtime_resource_fingerprint','resource_deep_probe_sha256'] as $m)if(!str_contains($backup,$m))$errors[]="backup resource admission missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(['RuntimeResourceEnvelopeIdentity','runtime_resource_fingerprint','resource_deep_probe_sha256','resource_installation_status'] as $m)if(!str_contains($installer,$m))$errors[]="installer resource lineage/admission missing [{$m}]";
    $deployment=$read($root.'/scripts/lib/deployment-generation.php').$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['resource_policy_sha256','config/nexora-resource-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment generation resource-policy binding missing [{$m}]";
    $c2=$read($root.'/scripts/n1-c2-laravel-runtime-certify.php');if(!str_contains($c2,'runtime-resource-status'))$errors[]='C2 must run deep runtime resource status';
    $c4=$read($root.'/scripts/n1-c4-evidence-prepare.php');foreach(['runtime_resource_envelope_verified','upgrade_low_memory_rejected','upgrade_low_disk_rejected','backup_scratch_capacity_verified','queue_wrong_resource_policy_rejected','resource_policy_drift_rejected','resource_cluster_convergence_verified','resource_deep_status_verified'] as $m)if(!str_contains($c4,$m))$errors[]="C4 resource evidence missing [{$m}]";
    $c6=$read($root.'/scripts/n1-c6-evidence-prepare.php');foreach(['runtime_resource_fingerprint','resource_deep_probe_sha256','resource_status','runtime_resource_policy_consistency','runtime_resource_capacity_minimums'] as $m)if(!str_contains($c6,$m))$errors[]="C6 resource evidence missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['queue_payload_schema'=>13,'deep_capacity_domains'=>7,'c2_resource_gate'=>1,'c4_resource_checks'=>8,'ha_resource_checks'=>2,'automatic_resource_mutation'=>0]];
}
