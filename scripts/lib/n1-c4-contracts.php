<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10C4Contracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/n1-c4-operations-certify.php',
        'scripts/n1-c4-operations-certify.bat',
        'scripts/n1-c4-operations-certify.ps1',
        'scripts/n1-c4-operations-certify.sh',
        'scripts/n1-c4-evidence-prepare.php',
        'scripts/n1-c4-evidence-verify.php',
        'scripts/zero-install-evidence-verify.php',
        'scripts/upgrade-rehearsal-evidence-verify.php',
        'scripts/backup-restore-evidence-verify.php',
        'scripts/lib/zero-install-contracts.php',
        'scripts/lib/upgrade-contracts.php',
        'app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0) $errors[]="Missing C4 artifact [{$file}].";

    $runner=(string)@file_get_contents($root.'/scripts/n1-c4-operations-certify.php');
    $gates=['c2-evidence','zero-install-contract','upgrade-contract','distributed-upgrade-contract','backup-contract','operator-evidence','c4-evidence'];
    foreach($gates as $gate) if(!str_contains($runner,"'{$gate}'")) $errors[]="C4 runner missing ordered gate [{$gate}].";
    foreach(['n1-c2-evidence-verify.php','zero-install-contract-verify.php','upgrade-contract-verify.php','n1-target-distributed-upgrade-contract-verify.php','backup-restore-evidence-verify.php','n1-c4-evidence-verify.php'] as $marker) if(!str_contains($runner,$marker)) $errors[]="C4 runner missing operational marker [{$marker}].";
    foreach(['certify-database-matrix.php','browser-evidence-verify.php','ha-evidence-verify.php','composer install','composer update','npm ci','npm install'] as $forbidden) if(stripos($runner,$forbidden)!==false) $errors[]="C4 must not own C1/C3/C5/C6 gate [{$forbidden}].";

    $prepare=(string)@file_get_contents($root.'/scripts/n1-c4-evidence-prepare.php');
    foreach(['zero-install-evidence.json','upgrade-rehearsal-evidence.json','backup-restore-evidence.json',"'fail'",'source_tree_sha256','certification_session_id','RUNBOOK.md','distributed_upgrade_lock_verified','migration_ledger_converged','cluster_recovery_lock_drill'] as $marker) if(!str_contains($prepare,$marker)) $errors[]="C4 evidence kit missing fail-closed/source-bound marker [{$marker}].";

    $verify=(string)@file_get_contents($root.'/scripts/n1-c4-evidence-verify.php');
    foreach(['zero_install_sha256','upgrade_rehearsal_sha256','backup_restore_sha256','c2_evidence_sha256','composer_lock_sha256','package_lock_sha256','reviewed_locks_sha256','certified_toolchain_sha256','certification_session_sha256'] as $binding) if(!str_contains($verify,$binding)) $errors[]="C4 evidence verifier missing binding [{$binding}].";

    $backup=(string)@file_get_contents($root.'/routes/console.php');
    foreach(['nexora:backup:create','nexora:backup:verify','nexora:backup:rehearse','nexora:restore:plan'] as $command) if(!str_contains($backup,$command)) $errors[]="C4 backup/recovery command missing [{$command}].";
    $backupService=(string)@file_get_contents($root.'/app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php');
    foreach(['automatic_destructive_restore','disposable target','verify($backup)'] as $marker) if(!str_contains($backupService,$marker)) $errors[]="C4 guarded restore contract missing [{$marker}].";

    $release=(string)@file_get_contents($root.'/config/nexora-release.php');
    if(!str_contains($release,'storage/app/nexora/n1-c4/')) $errors[]='Release policy must exclude C4 runtime evidence.';
    $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');
    if(!str_contains($zero,'storage/app/nexora/n1-c4')) $errors[]='Strict zero-state must reject C4 runtime evidence.';

    return ['errors'=>$errors,'warnings'=>[],'metrics'=>[
        'wrappers'=>count(array_filter(['scripts/n1-c4-operations-certify.bat','scripts/n1-c4-operations-certify.ps1','scripts/n1-c4-operations-certify.sh'],fn($f)=>is_file($root.'/'.$f))),
        'ordered_gates'=>count($gates),
        'operator_domains'=>3,
        'evidence_bindings'=>9,
        'dependency_installs'=>preg_match('/composer install|npm ci/i',$runner)===1?1:0,
        'db_matrix_calls'=>str_contains($runner,'certify-database-matrix.php')?1:0,
        'browser_ha_calls'=>preg_match('/browser-evidence|ha-evidence/i',$runner)===1?1:0,
        'automatic_destructive_restore'=>str_contains($backupService,"'automatic_destructive_restore' => true")?1:0,
    ]];
}
