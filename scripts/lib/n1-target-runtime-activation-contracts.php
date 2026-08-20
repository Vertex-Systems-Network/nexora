<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeRuntimeActivationContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-activation.php','app/Nexora/Cloud/Services/RuntimeActivationIdentity.php',
        'app/Console/Commands/Nexora/RuntimeActivationStatusCommand.php','app/Console/Commands/Nexora/RuntimeActivationRotateCommand.php',
        'tests/Architecture/N100V35RuntimeActivationArchitectureTest.php','tests/Unit/RuntimeVersionGuardQueuePayloadTest.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.5 runtime activation artifact missing [{$file}]";
    $config=$read($root.'/config/nexora-activation.php');foreach(['epoch_path','history_path','require_installed_match','require_process_epoch_match','queue_payload_schema','require_exact_queue_activation','opcache_revalidate_max_seconds'] as $m)if(!str_contains($config,$m))$errors[]="runtime activation config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeActivationIdentity.php');foreach(['processEpoch','currentEpoch','activation_fingerprint','framework_cache','snapshot_sha256','bootstrap/cache/config.php','bootstrap/cache/nexora/runtime.php','worker_restart_evidence_required','record_sha256','previous_activation_epoch','adoptCurrentEpochForProcess'] as $m)if(!str_contains($identity,$m))$errors[]="runtime activation identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['current_activation_epoch','process_activation_epoch','installed_activation_epoch','runtime_activation_fingerprint','process_activation_compatible','queue_payload_schema','different Nexora runtime activation epoch/cache fingerprint'] as $m)if(!str_contains($guard,$m))$errors[]="runtime activation guard missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['RuntimeActivationIdentity::class','payload_schema','activation_epoch','runtime_activation_fingerprint','ScheduledTaskStarting::class','RuntimeVersionGuard::class)->assertCompatible'] as $m)if(!str_contains($provider,$m))$errors[]="runtime provider activation fence missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['activation_epoch','runtime_activation_fingerprint','process_activation_epoch','framework_cache_sha256'] as $m)if(!str_contains($node,$m))$errors[]="runtime node activation advertisement missing [{$m}]";
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');foreach(['X-Nexora-Activation-Epoch','X-Nexora-Activation-Fingerprint','RuntimeActivationIdentity'] as $m)if(!str_contains($middleware,$m))$errors[]="web activation fence missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(["bootstrap('initial-install')",'activation_epoch','runtime_activation_fingerprint','runtime_activation_cache_sha256'] as $m)if(!str_contains($installer,$m))$errors[]="installer activation lineage missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_runtime_activation','Runtime activation/cache fingerprint changed','runtime_activation_rotated','queue:restart','activation_epoch','runtime_activation_fingerprint','runtime_activation_cache_sha256'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade activation transaction missing [{$m}]";
    $cluster=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php');foreach(['invalidActivations','targetActivation','runtime_activation_fingerprint','activation_epoch','target_activation_fingerprint'] as $m)if(!str_contains($cluster,$m))$errors[]="cluster activation convergence missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');if(!str_contains($ha,'runtime_activation_consistency'))$errors[]='HA readiness must verify runtime activation epoch/cache fingerprint consistency.';
    $builder=$read($root.'/scripts/build-production-release.php');
    foreach(['activationPolicyHash','activation_policy_sha256','runtime_activation_contract','framework_cache_fingerprint_required','process_epoch_fence',"automatic_php_fpm_restart'=>false"] as $m)if(!str_contains($builder,$m))$errors[]="production release activation policy missing [{$m}]";
    if(preg_match('/\$releaseInputs\s*=\s*\[(.*?)\n\];/s',$builder,$a)!==1||preg_match('/\$currentInputs\s*=\s*\[(.*?)\n\];/s',$builder,$b)!==1)$errors[]='production release input-freeze arrays missing';else{$keys=static fn(string $x):array=>array_values(array_unique(preg_match_all("/'([^']+)'\\s*=>/",$x,$m)?$m[1]:[]));$left=$keys($a[1]);$right=$keys($b[1]);sort($left);sort($right);if($left!==$right)$errors[]='production release input-freeze/revalidation key sets differ';if(!in_array('activation_policy_sha256',$left,true))$errors[]='production release input freeze must bind activation policy';}
    $env=$read($root.'/.env.production.example');foreach(['NEXORA_ACTIVATION_REQUIRE_INSTALLED_MATCH=true','NEXORA_ACTIVATION_REQUIRE_PROCESS_EPOCH_MATCH=true','NEXORA_QUEUE_REQUIRE_EXACT_ACTIVATION=true','NEXORA_ACTIVATION_REQUIRE_MAINTENANCE=true'] as $m)if(!str_contains($env,$m))$errors[]="production activation default missing [{$m}]";
    $zero=$read($root.'/scripts/zero-state-verify.php');if(!str_contains($zero,"'storage/app/nexora/runtime'"))$errors[]='strict source-zero must reject persisted runtime activation state';
    if(preg_match('/NEXORA_QUEUE_PAYLOAD_SCHEMA=(\d+)/',$env,$schema)!==1||(int)($schema[1]??0)<6)$errors[]='activation queue payload schema must remain 6 or newer';
    if(preg_match('/queue_payload_schema[^\n]*max\((\d+)/',$read($root.'/config/nexora-activation.php'),$cfgSchema)!==1||(int)($cfgSchema[1]??0)<6)$errors[]='activation config queue payload schema must remain 6 or newer';
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['activation_identity'=>1,'queue_payload_schema'=>max(6,(int)($schema[1]??6)),'process_epoch_fence'=>1,'framework_cache_fingerprint'=>1,'opcache_policy_observed'=>1,'automatic_php_fpm_restart'=>0,'automatic_traffic_restore'=>0]];
}
