<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeDistributedUpgradeContracts(string $root): array
{
    $errors=[];$warnings=[];$read=static fn(string $p):string=>is_file($p)?(string)file_get_contents($p):'';
    $required=[
        'app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php',
        'app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php',
        'app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php',
        'app/Console/Commands/Nexora/UpgradeClusterLockCommand.php',
        'app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php',
        'app/Console/Commands/Nexora/UpgradeQuiescenceCommand.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="distributed upgrade artifact missing [{$file}]";
    $config=$read($root.'/config/nexora-upgrade.php');foreach(['require_migration_ledger','require_cluster_quiescence','cluster_lease_name','platform-upgrade','cluster_lease_seconds','cluster_recovery_hold_seconds','cluster_fresh_node_seconds','cluster_require_scheduler_owner','cluster_require_shared_maintenance','cluster_require_runtime_quiescence','cluster_require_empty_queue'] as $m)if(!str_contains($config,$m))$errors[]="distributed upgrade config missing [{$m}]";
    $cluster=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeClusterCoordinator.php');foreach(['RuntimeLeaseManager','RuntimeActivityTracker','RuntimeVersionGuard','nx_runtime_nodes','nx_runtime_leases','scheduler-leader','Fresh peer nodes remain active','maintenance','recovery_required','holdForRecovery','verifyAndRenew','forceReleaseIfSafe','convergence','not_converged','peer_activities','queue_backlog','waitForCurrentQuiescence','activationAssessment','APP_MAINTENANCE_DRIVER=cache','app.maintenance.driver','app.maintenance.store'] as $m)if(!str_contains($cluster,$m))$errors[]="cluster coordinator missing [{$m}]";
    if(str_contains($cluster,'setStatus(\'draining\')')||str_contains($cluster,'setStatus("draining")'))$errors[]='cluster coordinator must not automatically drain peer nodes';
    $ledger=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeMigrationLedger.php');foreach(['ledger_sha256','assertUnchanged','assertConverged','pending','duplicates','previously applied migrations disappeared','convergence_sha256'] as $m)if(!str_contains($ledger,$m))$errors[]="migration ledger missing [{$m}]";
    $manager=$read($root.'/app/Nexora/Foundation/Upgrade/UpgradeManager.php');foreach(['compatibility_assessment_sha256','migration_ledger_before','cluster_preflight','cluster->acquire','cluster->verifyAndRenew','cluster->holdForRecovery','migration_ledger_converged','last_upgrade_migration_convergence_sha256','last_upgrade_cluster_preflight_sha256'] as $m)if(!str_contains($manager,$m))$errors[]="upgrade manager distributed safety missing [{$m}]";
    $commands=$read($root.'/app/Console/Commands/Nexora/UpgradeClusterStatusCommand.php').$read($root.'/app/Console/Commands/Nexora/UpgradeNodeStatusCommand.php').$read($root.'/app/Console/Commands/Nexora/UpgradeClusterLockCommand.php').$read($root.'/app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php').$read($root.'/app/Console/Commands/Nexora/UpgradeQuiescenceCommand.php');foreach(['nexora:upgrade:cluster-status','nexora:upgrade:node-status','nexora:upgrade:cluster-lock','nexora:upgrade:scheduler-lease','nexora:upgrade:quiescence','--confirm= : Must equal SET','--confirm= : Must equal RELEASE','automatic_release'] as $m)if(!str_contains($commands,$m))$errors[]="distributed operator command missing [{$m}]";
    $heartbeat=$read($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');foreach(['isReady()','503','Retry-After'] as $m)if(!str_contains($heartbeat,$m))$errors[]="runtime draining middleware missing [{$m}]";
    $leadership=$read($root.'/app/Nexora/Cloud/Services/ClusterLeadership.php');if(!str_contains($leadership,'nodes->isReady()')||!str_contains($leadership,'versions->compatible()'))$errors[]='draining/maintenance or mixed-version nodes must not acquire scheduler leadership';
    $appProvider=$read($root.'/app/Providers/AppServiceProvider.php');foreach(['NodeManager::class)->isReady()','RuntimeVersionGuard::class)->compatible()',"queue.worker')->shouldQuit=true",'RuntimeActivityTracker::class'] as $m)if(!str_contains($appProvider,$m))$errors[]="queue drain contract missing [{$m}]";
    $lockCommand=$read($root.'/app/Console/Commands/Nexora/UpgradeClusterLockCommand.php');foreach(['lease is still live','recovery_required','--upgrade-id','hash_equals'] as $m)if(!str_contains($lockCommand,$m))$errors[]="distributed lock release safety missing [{$m}]";
    $schedulerCommand=$read($root.'/app/Console/Commands/Nexora/UpgradeSchedulerLeaseCommand.php');foreach(['scheduler-leader','this runtime node is active','belongs to another runtime node'] as $m)if(!str_contains($schedulerCommand,$m))$errors[]="scheduler release safety missing [{$m}]";
    $provider=$read($root.'/app/Providers/NexoraServiceProvider.php');

foreach(['UpgradeClusterStatusCommand::class','UpgradeNodeStatusCommand::class','UpgradeClusterLockCommand::class','UpgradeSchedulerLeaseCommand::class','UpgradeQuiescenceCommand::class'] as $m)if(!str_contains($provider,$m))$errors[]="service provider missing distributed upgrade command [{$m}]";
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['distributed_lease'=>1,'migration_ledger'=>1,'operator_commands'=>5,'automatic_peer_drain'=>0,'web_drain_503'=>1,'queue_worker_drain'=>1,'scheduler_drain_fence'=>1,'shared_maintenance_gate'=>1,'automatic_database_rollback'=>0]];
}
