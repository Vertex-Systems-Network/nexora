<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeHostClockContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'config/nexora-host-runtime.php',
        'app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php',
        'app/Console/Commands/Nexora/RuntimeHostStatusCommand.php',
        'scripts/lib/n1-target-host-clock-contracts.php',
        'scripts/n1-target-host-clock-contract-verify.php',
    ];
    foreach($required as $f)if(!is_file($root.'/'.$f)||filesize($root.'/'.$f)===0)$errors[]="host/clock artifact missing [{$f}]";
    $config=$read($root.'/config/nexora-host-runtime.php');foreach(['require_exact_host_profile','required_timezone','require_database_clock_anchor','max_database_clock_skew_ms','queue_future_skew_seconds','allowed_umasks','require_atomic_rename','require_flock','require_secure_random','queue_payload_schema'] as $m)if(!str_contains($config,$m))$errors[]="host/clock config missing [{$m}]";
    $identity=$read($root.'/app/Nexora/Cloud/Services/RuntimeHostClockIdentity.php');foreach(['databaseEpochSeconds','databaseNow','verifyQueueTimestamp','runtime_timezone','intl_default_locale','database_clock_skew','atomic_rename','flock','secure_random','generated_unix_ms','CURRENT_TIMESTAMP','clock_timestamp','SYSUTCDATETIME'] as $m)if(!str_contains($identity,$m))$errors[]="host/clock identity missing [{$m}]";
    $lease=$read($root.'/app/Nexora/Cloud/Services/RuntimeLeaseManager.php');if(!str_contains($lease,'RuntimeHostClockIdentity')||!str_contains($lease,'databaseNow'))$errors[]='distributed runtime lease manager must use shared database clock';
    $node=$read($root.'/app/Nexora/Cloud/Services/NodeManager.php');foreach(['runtime_host_fingerprint','databaseNow','host_timezone','host_locale'] as $m)if(!str_contains($node,$m))$errors[]="runtime node host identity missing [{$m}]";
    $guard=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['max(13','runtime_host_fingerprint','verifyQueueTimestamp','different Nexora host/platform/timezone/locale profile'] as $m)if(!str_contains($guard,$m))$errors[]="runtime queue/host fence missing [{$m}]";
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');
    foreach(['RuntimeHostClockIdentity','runtime_host_fingerprint','generated_unix_ms','date_default_timezone_set','Locale::setDefault'] as $m)if(!str_contains($provider,$m))$errors[]="application runtime host normalization/payload missing [{$m}]";
    $upgrade=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['source_host_clock','host_clock_attested','runtime_host_fingerprint','host_clock_deep_probe_sha256','databaseNow'] as $m)if(!str_contains($upgrade,$m))$errors[]="upgrade host/clock binding missing [{$m}]";
    $ha=$read($root.'/app/Nexora/Cloud/Services/HaReadinessService.php');foreach(['runtime_host_profile_consistency','local_host_clock_profile','databaseNow'] as $m)if(!str_contains($ha,$m))$errors[]="HA host/clock convergence missing [{$m}]";
    $deployment=$read($root.'/scripts/lib/deployment-generation.php').$read($root.'/app/Nexora/Cloud/Services/RuntimeDeploymentIdentity.php');foreach(['host_policy_sha256','config/nexora-host-runtime.php'] as $m)if(!str_contains($deployment,$m))$errors[]="deployment generation host-policy binding missing [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['queue_payload_schema'=>13,'shared_clock_anchor'=>1,'deep_host_probe'=>1,'automatic_ntp_mutation'=>0,'automatic_timezone_mutation'=>0]];
}
