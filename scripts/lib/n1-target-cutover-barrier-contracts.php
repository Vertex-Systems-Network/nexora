<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeCutoverBarrierContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'app/Nexora/Cloud/Services/RuntimeLeaseManager.php',
        'app/Nexora/Cloud/Services/RuntimeActivityTracker.php',
        'app/Nexora/Cloud/Services/RuntimeVersionGuard.php',
        'app/Http/Middleware/RuntimeNodeHeartbeat.php',
        'app/Providers/AppServiceProvider.php',
        'scripts/frontend-contract-verify.php',
        'app/Console/Commands/Nexora/UpgradeCutoverStatusCommand.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="v3.2 cutover/frontend artifact missing [{$file}]";

    $leases=$read($root.'/app/Nexora/Cloud/Services/RuntimeLeaseManager.php');
    foreach(['acquireActivityUnlessBarrierActive','lockForUpdate','barrierActive','barrierStatus'] as $m)if(!str_contains($leases,$m))$errors[]="atomic activity/barrier lease contract missing [{$m}]";
    if(substr_count($leases,"where('name',\$barrierName)->lockForUpdate()")<1)$errors[]='Runtime activity admission must lock the same configured platform-upgrade barrier row before publishing activity.';

    $activity=$read($root.'/app/Nexora/Cloud/Services/RuntimeActivityTracker.php');
    foreach(['runtime_admission_barrier_required','acquireActivityUnlessBarrierActive','platform upgrade cutover barrier','admissionStatus','new_activity_allowed'] as $m)if(!str_contains($activity,$m))$errors[]="runtime activity admission barrier missing [{$m}]";

    $version=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');
    foreach(['queue_payload_require_metadata','legacy queue payload lacks required Nexora runtime metadata','queue_payload_schema','queue_payload_require_exact_version','different Nexora platform version'] as $m)if(!str_contains($version,$m))$errors[]="exact queue payload fence missing [{$m}]";
    if(str_contains($version,'incompatible Nexora major version'))$errors[]='Queue payload compatibility must not fall back to major-version-only compatibility after v3.2.';

    $provider=$read($root.'/app/Providers/AppServiceProvider.php');
    foreach(["'payload_schema'=>max(",'RuntimeActivityTracker::class','ScheduledTaskStarting'] as $m)if(!str_contains($provider,$m))$errors[]="runtime provider cutover marker missing [{$m}]";
    $scheduledLine='';foreach(preg_split('/\R/',$provider)?:[] as $line){if(str_contains($line,'ScheduledTaskStarting::class')){$scheduledLine=$line;break;}}if($scheduledLine===''||str_contains($scheduledLine,'catch(\Throwable'))$errors[]='Scheduled task activity admission must fail closed instead of swallowing the cutover barrier refusal.';

    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');
    foreach(['X-Nexora-Cutover-Barrier','runtime admission is temporarily closed','503'] as $m)if(!str_contains($middleware,$m))$errors[]="web cutover admission fence missing [{$m}]";

    $config=$read($root.'/config/nexora-upgrade.php');
    foreach(['runtime_admission_barrier_required','queue_payload_schema','queue_payload_require_metadata','queue_payload_require_exact_version'] as $m)if(!str_contains($config,$m))$errors[]="v3.2 cutover config missing [{$m}]";

    $env=$read($root.'/.env.production.example');
    foreach(['NEXORA_UPGRADE_RUNTIME_ADMISSION_BARRIER_REQUIRED=true','NEXORA_QUEUE_PAYLOAD_REQUIRE_METADATA=true','NEXORA_QUEUE_PAYLOAD_REQUIRE_EXACT_VERSION=true'] as $m)if(!str_contains($env,$m))$errors[]="production template missing v3.2 cutover default [{$m}]";if(preg_match('/NEXORA_QUEUE_PAYLOAD_SCHEMA=(\d+)/',$env,$schemaMatch)!==1||(int)($schemaMatch[1]??0)<2)$errors[]='production queue payload schema must remain 2 or newer';

    $cutoverCommand=$read($root.'/app/Console/Commands/Nexora/UpgradeCutoverStatusCommand.php');foreach(['nexora:upgrade:cutover-status','runtime_admission','queue_payload_policy'] as $m)if(!str_contains($cutoverCommand,$m))$errors[]="cutover-status operator command missing [{$m}]";if(preg_match('/[\"\']automatic_cutover[\"\']\s*=>\s*false/', $cutoverCommand)!==1)$errors[]='cutover-status must remain read-only/non-automatic.';

    $frontend=$read($root.'/scripts/frontend-contract-verify.php');
    foreach(['WorkflowFormData','RequestPayload','Deliberate shallow boundary: SSO configuration and secret payload default server-side.','WriterValue','documentError','ButtonLink'] as $m)if(!str_contains($frontend,$m))$errors[]="frontend Inertia v3 regression contract missing [{$m}]";

    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>[
        'atomic_cutover_barrier'=>1,
        'queue_payload_schema'=>4,
        'legacy_payload_compatibility'=>0,
        'same_major_old_payload_compatibility'=>0,
        'web_barrier_fail_closed'=>1,
        'scheduler_barrier_fail_closed'=>1,
        'automatic_peer_drain'=>0,
    ]];
}
