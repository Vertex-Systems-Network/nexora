<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeRuntimeEngineContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-engine.php','app/Nexora/Cloud/Services/RuntimeEngineIdentity.php',
        'app/Console/Commands/Nexora/RuntimeEngineStatusCommand.php','tests/Architecture/N100V36RuntimeEngineArchitectureTest.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.6 runtime-engine artifact missing [{$file}]";
    $config=$read($root.'/config/nexora-engine.php');foreach(['require_exact_php_patch','require_exact_extension_profile','require_exact_pdo_drivers','required_extensions','compatibility_extensions','compatibility_ini','queue_payload_schema','NEXORA_QUEUE_PAYLOAD_SCHEMA'] as $m)if(!str_contains($config,$m))$errors[]="runtime-engine config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeEngineIdentity.php');foreach(['php_version','php_version_id','zend_version','extension_profile_sha256','pdo_drivers_sha256','openssl_version','sodium_version','icu_version','process_profile_fingerprint','required_extensions_missing'] as $m)if(!str_contains($identity,$m))$errors[]="runtime-engine identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['current_runtime_engine_fingerprint','installed_runtime_engine_fingerprint','runtime_engine_compatible','queue_payload_schema','runtime_engine_fingerprint','different Nexora PHP runtime engine/extension profile'] as $m)if(!str_contains($guard,$m))$errors[]="runtime-engine guard missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['RuntimeEngineIdentity::class','payload_schema','runtime_engine_fingerprint'] as $m)if(!str_contains($provider,$m))$errors[]="queue runtime-engine fence missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_engine_fingerprint','php_version','extension_profile_sha256','pdo_drivers_sha256'] as $m)if(!str_contains($node,$m))$errors[]="node runtime-engine advertisement missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');if(!str_contains($ha,'runtime_engine_consistency'))$errors[]='HA readiness must verify runtime-engine consistency.';
    $cluster=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php');foreach(['invalidEngines','targetEngine','runtime_engine_fingerprint','target_runtime_engine_fingerprint'] as $m)if(!str_contains($cluster,$m))$errors[]="upgrade cluster engine convergence missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['target_runtime_engine','PHP runtime engine/extension fingerprint changed','last_upgrade_runtime_engine_fingerprint','extension_profile_sha256','pdo_drivers_sha256'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade runtime-engine binding missing [{$m}]";
    $installer=$read($root.'/app/Nexora/Installation/Installer.php');foreach(['RuntimeEngineIdentity','runtime_engine_fingerprint','extension_profile_sha256','pdo_drivers_sha256'] as $m)if(!str_contains($installer,$m))$errors[]="installer runtime-engine lineage missing [{$m}]";
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');if(!str_contains($middleware,'X-Nexora-Engine-Fingerprint'))$errors[]='web runtime-engine response header missing.';
    $c4=$read($root.'/scripts/n1-c4-evidence-prepare.php').$read($root.'/scripts/lib/final-evidence.php');foreach(['runtime_engine_fingerprint_verified','php_patch_drift_rejected','extension_profile_drift_rejected','pdo_driver_set_verified','queue_wrong_engine_rejected','engine_cluster_convergence_verified','engine_deep_status_verified'] as $m)if(!str_contains($c4,$m))$errors[]="C4 runtime-engine evidence missing [{$m}]";
    $c6=$read($root.'/scripts/n1-c6-evidence-prepare.php').$read($root.'/scripts/lib/n1-c6-contracts.php');if(!str_contains($c6,'runtime_engine_fingerprint_consistency'))$errors[]='C6 HA evidence must include runtime-engine fingerprint consistency.';
    $builder=$read($root.'/scripts/build-production-release.php');foreach(['enginePolicyHash','engine_policy_sha256','runtime_engine_contract','exact_php_patch_required','exact_extension_profile_required','exact_pdo_driver_set_required','nexora:runtime:engine-status --deep'] as $m)if(!str_contains($builder,$m))$errors[]="production release runtime-engine contract missing [{$m}]";
    $env=$read($root.'/.env.production.example');foreach(['NEXORA_ENGINE_REQUIRE_EXACT_PHP_PATCH=true','NEXORA_ENGINE_REQUIRE_EXACT_EXTENSIONS=true','NEXORA_ENGINE_REQUIRE_EXACT_PDO_DRIVERS=true'] as $m)if(!str_contains($env,$m))$errors[]="production runtime-engine default missing [{$m}]";
    if(preg_match('/NEXORA_QUEUE_PAYLOAD_SCHEMA=(\d+)/',$env,$schema)!==1||(int)($schema[1]??0)<6)$errors[]='runtime-engine queue payload schema must remain 6 or newer';
    if(preg_match('/queue_payload_schema[^\n]*max\((\d+)/',$config,$cfgSchema)!==1||(int)($cfgSchema[1]??0)<6)$errors[]='runtime-engine config queue payload schema must remain 6 or newer';
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['engine_identity'=>1,'queue_payload_schema'=>max(6,(int)($schema[1]??6)),'ha_engine_check'=>1,'upgrade_engine_binding'=>1,'automatic_php_runtime_mutation'=>0]];
}
