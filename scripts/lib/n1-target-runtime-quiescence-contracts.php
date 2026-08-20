<?php

declare(strict_types=1);
/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeRuntimeQuiescenceContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=['app/Nexora/Cloud/Services/RuntimeActivityTracker.php','app/Nexora/Cloud/Services/RuntimeVersionGuard.php','app/Nexora/Foundation/Upgrade/UpgradeMigrationSafety.php','app/Console/Commands/Nexora/UpgradeQuiescenceCommand.php'];foreach($required as $f)if(!is_file($root.'/'.$f)||filesize($root.'/'.$f)===0)$errors[]='runtime quiescence artifact missing ['.$f.']';
    $activity=$read($root.'/app/Nexora/Cloud/Services/RuntimeActivityTracker.php');foreach(['runtime-activity:','web','queue','scheduler','queueBacklog','waitForNodeQuiescence','quiescence_sha256'] as $m)if(!str_contains($activity,$m))$errors[]='runtime activity tracker missing ['.$m.']';
    $version=$read($root.'/app/Nexora/Cloud/Services/RuntimeVersionGuard.php');foreach(['installed_version','queuePayload','payload_schema','legacy queue payload lacks required Nexora runtime metadata','different Nexora platform version'] as $m)if(!str_contains($version,$m))$errors[]='runtime version guard missing ['.$m.']';
    $middleware=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');foreach(['RuntimeActivityTracker','RuntimeVersionGuard','X-Nexora-Runtime-Version','503','finally'] as $m)if(!str_contains($middleware,$m))$errors[]='web runtime fence missing ['.$m.']';
    $provider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['Queue::createPayloadUsing','RuntimeActivityTracker::class','ScheduledTaskStarting','ScheduledTaskFinished','ScheduledTaskFailed','queue.worker'] as $m)if(!str_contains($provider,$m))$errors[]='queue/scheduler activity fence missing ['.$m.']';
    $cluster=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php');foreach(['peer_activities','queue_backlog','waitForCurrentQuiescence','activationAssessment','forceReleaseIfSafe(?string $expectedUpgradeId'] as $m)if(!str_contains($cluster,$m))$errors[]='cluster quiescence contract missing ['.$m.']';
    $manager=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['migration_safety','runtime_quiesced','waitForCurrentQuiescence','last_upgrade_runtime_quiescence_sha256','last_upgrade_migration_safety_sha256'] as $m)if(!str_contains($manager,$m))$errors[]='upgrade manager runtime safety missing ['.$m.']';
    $migration=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeMigrationSafety.php');
    foreach(['destructiveRules','schema-drop','column-drop','column-change','raw-destructive-sql','automatic_destructive_migration_approval'] as $m)if(!str_contains($migration,$m))$errors[]='migration safety scanner missing ['.$m.']';
    $config=$read($root.'/config/nexora-upgrade.php');foreach(['cluster_require_runtime_quiescence','cluster_require_empty_queue','cluster_queue_names','activity_ttl_web_seconds','activity_ttl_queue_seconds','activity_ttl_scheduler_seconds','block_destructive_pending_migrations','runtime_admission_barrier_required','queue_payload_schema','queue_payload_require_metadata','queue_payload_require_exact_version'] as $m)if(!str_contains($config,$m))$errors[]='runtime quiescence config missing ['.$m.']';
    $clusterStatus=$read($root.'/app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php');foreach(['InstallationState','source_version','target_version'] as $m)if(!str_contains($clusterStatus,$m))$errors[]='cluster status installed-version derivation missing ['.$m.']';
    $node=$read($root.'/app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php');foreach(['activationAssessment','running or recovery_required','Activation refused'] as $m)if(!str_contains($node,$m))$errors[]='node reactivation guard missing ['.$m.']';
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['activity_kinds'=>3,'queue_payload_schema'=>4,'migration_destructive_rules'=>4,'automatic_peer_drain'=>0,'automatic_destructive_migration_approval'=>0,'automatic_database_rollback'=>0]];
}
