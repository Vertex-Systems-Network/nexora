<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeProcessPlaneContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $rel):string=>is_file($root.'/'.$rel)?(string)file_get_contents($root.'/'.$rel):'';
    foreach([
        'config/nexora-process-runtime.php',
        'app/Nexora/Cloud/Services/RuntimeProcessPlane.php',
        'app/Console/Commands/Nexora/RuntimeProcessHeartbeatCommand.php',
        'app/Console/Commands/Nexora/RuntimeProcessStatusCommand.php',
        'scripts/lib/n1-target-process-plane-contracts.php',
        'scripts/n1-target-process-plane-contract-verify.php',
    ] as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="process-plane artifact missing [{$file}]";

    $cfg=$read('config/nexora-process-runtime.php');foreach(['require_exact_process_policy','lease_seconds','heartbeat_throttle_seconds','minimum_web_nodes','minimum_queue_nodes','minimum_scheduler_nodes','reject_indefinite_queue_blocking_for_ha','queue_max_block_seconds','queue_payload_schema'] as $m)if(!str_contains($cfg,$m))$errors[]="process runtime config missing [{$m}]";
    $service=$read('app/Nexora/Cloud/Services/RuntimeProcessPlane.php');foreach(['runtime-process:','web','queue','scheduler','acquireOrRenew','databaseNow','queue_blocking_liveness_safe','requiredCounts','process_policy_fingerprint','queue_schema_current'] as $m)if(!str_contains($service,$m))$errors[]="runtime process plane missing [{$m}]";
    $provider=$read('app/Providers/AppServiceProvider.php');foreach(['RuntimeProcessPlane','runtime_process_fingerprint',"heartbeat('queue')",'Queue::looping',"'payload_schema'=>max(13"] as $m)if(!str_contains($provider,$m))$errors[]="queue process heartbeat/fence missing [{$m}]";
    $web=$read('app/Http/Middleware/RuntimeNodeHeartbeat.php');foreach(['RuntimeProcessPlane',"heartbeat('web')",'X-Nexora-Process-Fingerprint'] as $m)if(!str_contains($web,$m))$errors[]="web process heartbeat missing [{$m}]";
    $runtimeContracts=$read('scripts/lib/laravel-runtime-contracts.php');foreach(['nonLeaderInfrastructureHeartbeats','nexora:runtime:process-heartbeat scheduler'] as $m)if(!str_contains($runtimeContracts,$m))$errors[]="scheduler runtime contract missing exact non-leader infrastructure heartbeat allowance [{$m}]";
    $console=$read('routes/console.php');foreach(["nexora:runtime:process-heartbeat scheduler","nexora:node:heartbeat"] as $m)if(!str_contains($console,$m))$errors[]="scheduler process heartbeat missing [{$m}]";
    $node=$read('app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_process_fingerprint','runtime_process_policy_status'] as $m)if(!str_contains($node,$m))$errors[]="runtime node process policy metadata missing [{$m}]";
    $ha=$read('app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['local_process_plane','addProcessQuorumChecks',"['web', 'queue', 'scheduler']",'_process_quorum','runtime_process_policy_consistency'] as $m)if(!str_contains($ha,$m))$errors[]="HA process role readiness missing [{$m}]";
    $guard=$read('app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['runtime_process_fingerprint','runtime_process_compatible','different Nexora runtime process-role policy','max(13'] as $m)if(!str_contains($guard,$m))$errors[]="runtime process version/queue fence missing [{$m}]";
    $installer=$read('app/Nexora/Installation/Installer.php');if(!str_contains($installer,'runtime_process_fingerprint')||!str_contains($installer,'process_installation_status')||!str_contains($installer,'installationReadiness->assertReady'))$errors[]='installation process-policy lineage missing';
    $upgrade=$read('app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_process_plane','process_plane_attested','runtime_process_fingerprint','last_upgrade_process_fingerprint'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade process-plane binding missing [{$m}]";
    $deployment=$read('scripts/lib/deployment-generation.php').$read('app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['process_policy_sha256','config/nexora-process-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment process policy binding missing [{$m}]";
    $release=$read('scripts/build-production-release.php').$read('scripts/lib/final-release-seal.php').$read('scripts/release-provenance.php');foreach(['runtime_process_plane_contract','process_policy_sha256','runtime:process-status --assert-installed --assert-live'] as $m)if(!str_contains($release,$m))$errors[]="release process-plane binding missing [{$m}]";

    $health=$read('app/Http/Controllers/Admin/SystemHealthController.php');foreach(['Runtime Process Policy','processPlane'] as $m)if(!str_contains($health,$m))$errors[]="Admin system health process-plane visibility missing [{$m}]";
    $status=$read('app/Console/Commands/Nexora/RuntimeDeploymentStatusCommand.php').$read('app/Console/Commands/Nexora/UpgradeCutoverStatusCommand.php').$read('app/Console/Commands/Nexora/UpgradeLineageExportCommand.php');foreach(['runtime_process_plane','runtime_process_fingerprint','exact_process_policy_required','last_upgrade_process_fingerprint'] as $m)if(!str_contains($status,$m))$errors[]="operator process-plane visibility missing [{$m}]";
    $env=$read('.env.production.example');foreach(['NEXORA_PROCESS_REQUIRE_EXACT_POLICY=true','NEXORA_PROCESS_MIN_WEB_NODES=2','NEXORA_PROCESS_MIN_QUEUE_NODES=2','NEXORA_PROCESS_MIN_SCHEDULER_NODES=1','NEXORA_PROCESS_REJECT_INDEFINITE_QUEUE_BLOCKING_HA=true','NEXORA_QUEUE_PAYLOAD_SCHEMA=13'] as $m)if(!str_contains($env,$m))$errors[]="production process policy default missing [{$m}]";
    $policy=$read('app/Nexora/Cloud/Services/RuntimePolicyPlaneIdentity.php');foreach(['process_runtime','process_role_policy_fail_closed'] as $m)if(!str_contains($policy,$m))$errors[]="effective policy plane does not bind process policy [{$m}]";
    return ['errors'=>array_values(array_unique($errors)),'warnings'=>$warnings,'metrics'=>['roles'=>3,'queue_payload_schema'=>13,'automatic_process_start_stop'=>0,'live_role_quorums'=>3]];
}
