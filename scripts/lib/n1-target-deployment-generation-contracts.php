<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeDeploymentGenerationContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'scripts/lib/deployment-generation.php','app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php','app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Http/Middleware/RuntimeNodeHeartbeat.php','app/Http/Middleware/HandleInertiaRequests.php','resources/js/admin/runtime/deployment-fence.ts',
        'app/Console/Commands/Nexora/RuntimeDeploymentStatusCommand.php','tests/Architecture/N100V33DeploymentGenerationArchitectureTest.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.3 deployment-generation artifact missing [{$file}]";

    $generation=$read($root.'/scripts/lib/deployment-generation.php');foreach(['platform_version','source_tree_sha256','frontend_manifest_sha256','composer_lock_sha256','package_lock_sha256','runtime_policy_sha256','upgrade_policy_sha256','session_schema','nexoraDeploymentGeneration'] as $m)if(!str_contains($generation,$m))$errors[]="deployment generation material missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['production-manifest','admitted-update','installed-metadata','source-fallback','deepVerify','assetVersion','cacheNamespace','deployment generation','Admitted update deployment generation does not match','generation_matches_declared','installedDriftAssessment','session_schema'] as $m)if(!str_contains($identity,$m))$errors[]="runtime deployment identity missing [{$m}]";
    $version=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['current_generation','installed_generation','generation_compatible','queue_payload_require_exact_generation','different Nexora deployment generation','max('] as $m)if(!str_contains($version,$m))$errors[]="runtime generation guard missing [{$m}]";
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['deployment_generation','frontend_manifest_sha256','RuntimeDeploymentIdentity'] as $m)if(!str_contains($node,$m))$errors[]="runtime node generation advertisement missing [{$m}]";
    $cluster=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php');foreach(['invalidGenerations','targetGeneration','sourceGeneration','target_generation','deployment_generation'] as $m)if(!str_contains($cluster,$m))$errors[]="cluster generation convergence missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['target_deployment','Target deployment generation changed','deployment_generation','release_source_tree_sha256','frontend_manifest_sha256','cache_namespace','session_schema'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade deployment-generation binding missing [{$m}]";
    $trusted=$read($root.'/scripts/lib/trusted-update.php').$read($root.'/scripts/trusted-update-candidate.php').$read($root.'/scripts/trusted-update-admit-candidate.php');foreach(['target_deployment_generation','target_frontend_manifest_sha256','deployment_generation','deployment_materials'] as $m)if(!str_contains($trusted,$m))$errors[]="trusted update deployment identity binding missing [{$m}]";
    $builder=$read($root.'/scripts/build-production-release.php');foreach(['runtime_deployment','runtimeDeploymentGeneration','runtimeDeploymentMaterials','cache_namespace','upgrade_policy_sha256','session_schema'] as $m)if(!str_contains($builder,$m))$errors[]="production release deployment identity missing [{$m}]";
    $seal=$read($root.'/scripts/lib/final-release-seal.php').$read($root.'/scripts/release-offline-verify.php').$read($root.'/scripts/lib/release-artifact.php');foreach(['deployment_generation','nexoraDeploymentGeneration','runtime_deployment'] as $m)if(!str_contains($seal,$m))$errors[]="release verification deployment generation binding missing [{$m}]";

    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');foreach(['X-Nexora-Deployment-Generation','X-Nexora-Deployment-Mismatch','X-Nexora-Asset-Version','X-Nexora-Session-Schema','nexora.runtime_session_schema','expectsJson'] as $m)if(!str_contains($middleware,$m))$errors[]="web/client/session generation fence missing [{$m}]";
    $inertia=$read($root.'/app/Http/Middleware/HandleInertiaRequests.php');foreach(['public function version(Request $request)','assetVersion','deployment'] as $m)if(!str_contains($inertia,$m))$errors[]="Inertia asset-generation fence missing [{$m}]";
    $frontend=$read($root.'/resources/js/admin/runtime/deployment-fence.ts').$read($root.'/resources/js/app.tsx');foreach(['X-Nexora-Deployment-Generation','X-Nexora-Deployment-Mismatch','window.location.reload','installDeploymentFetchFence'] as $m)if(!str_contains($frontend,$m))$errors[]="frontend stale-client generation fence missing [{$m}]";
    $cache=$read($root.'/config/cache.php');foreach(['NEXORA_CACHE_GENERATION_FENCING','NEXORA_CACHE_EPOCH','target_deployment_generation','deployment_generation','nexoraCachePrefix'] as $m)if(!str_contains($cache,$m))$errors[]="cache generation namespace fence missing [{$m}]";
    $status=$read($root.'/app/Console/Commands/Nexora/RuntimeDeploymentStatusCommand.php');foreach(['nexora:runtime:deployment-status','--deep','deepVerify','mutation_performed'] as $m)if(!str_contains($status,$m))$errors[]="deployment status/deep verification command missing [{$m}]";

    $config=$read($root.'/config/nexora-upgrade.php').$read($root.'/config/nexora-runtime.php');foreach(['queue_payload_schema','queue_payload_require_exact_generation','runtime_generation_require_installed_match','client_generation_fence_required','session_schema_enforced','cache_generation_fencing'] as $m)if(!str_contains($config,$m))$errors[]="v3.3 deployment config missing [{$m}]";
    $env=$read($root.'/.env.production.example');foreach(['NEXORA_QUEUE_PAYLOAD_REQUIRE_EXACT_GENERATION=true','NEXORA_CACHE_GENERATION_FENCING=true','NEXORA_SESSION_SCHEMA_ENFORCED=true','NEXORA_CLIENT_GENERATION_REQUIRE_JSON_HEADER=true'] as $m)if(!str_contains($env,$m))$errors[]="production template missing deployment-generation default [{$m}]";if(preg_match('/NEXORA_QUEUE_PAYLOAD_SCHEMA=(\d+)/',$env,$schema)!==1||(int)($schema[1]??0)<4)$errors[]='deployment-generation queue payload schema must remain 4 or newer';

    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>[
        'deployment_generation'=>1,'queue_payload_schema'=>4,'same_version_wrong_generation_compatibility'=>0,'inertia_asset_reload_fence'=>1,
        'raw_json_client_fence'=>1,'cache_generation_namespace'=>1,'session_schema_guard'=>1,'deep_deployment_verification'=>1,'automatic_cache_purge'=>0,
    ]];
}
