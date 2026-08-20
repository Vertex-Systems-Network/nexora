<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10TargetExecutionContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/n1-target-execution.php','scripts/n1-target-execution.bat','scripts/n1-target-execution.ps1','scripts/n1-target-execution.sh',
        'scripts/n1-c1-dependency-certify.php','scripts/n1-c2-laravel-runtime-certify.php','scripts/n1-c3-database-matrix-certify.php',
        'scripts/n1-c4-operations-certify.php','scripts/n1-c5-browser-performance-certify.php','scripts/n1-c6-final-certify.php',
        'scripts/dependency-lock-refresh.php','scripts/refresh-dependency-locks.bat','scripts/refresh-dependency-locks.ps1','scripts/refresh-dependency-locks.sh',
        'scripts/dependency-lock-promote.php','scripts/promote-reviewed-dependency-locks.bat','scripts/promote-reviewed-dependency-locks.ps1','scripts/promote-reviewed-dependency-locks.sh',
        'scripts/dependency-lock-promotion-recover.php','scripts/recover-dependency-lock-promotion.bat','scripts/recover-dependency-lock-promotion.ps1','scripts/recover-dependency-lock-promotion.sh','scripts/lib/dependency-lock-intake.php',
        'scripts/lib/target-composer.php','scripts/lib/target-support-capsule.php','scripts/n1-target-support-capsule.php',
        'scripts/target-prerequisite-restart-verify.php','scripts/target-prerequisite-restart-verify.bat','scripts/target-prerequisite-restart-verify.ps1','scripts/target-prerequisite-restart-verify.sh',
        'scripts/n1-certification-session.php','scripts/n1-certification-session.bat','scripts/n1-certification-session.ps1','scripts/n1-certification-session.sh','scripts/lib/n1-target-run-lock.php',
    ];
    foreach($required as $file)if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="Missing target execution artifact [{$file}].";

    $runner=(string)@file_get_contents($root.'/scripts/n1-target-execution.php');
    $ordered=['source-certification','restart-verification','prerequisite-remediation','lock-refresh','lock-review','lock-review-verify','resume-c1','c1','resume-c2','c2','resume-c3','c3','certification-session','prepare-c4','prepare-c5','prepare-c6','c4','c5','c6'];
    foreach($ordered as $gate)if(!str_contains($runner,"'{$gate}'"))$errors[]="Target execution runner missing phase [{$gate}].";
    foreach(['--install-deps','--apply-extensions','--verify-restart','--refresh-locks','--confirm-refresh=','--review-locks','--reviewer=','--confirm-review=','--resume-latest','--prepare-kits','--c4-evidence=','--c5-evidence=','--c6-evidence=','--base-url=','--operator=','operator-action-required','lock-review-required','n1-c6-final-certify.php','dependency-lock-promote.php','PROMOTE-REVIEWED','recover-dependency-lock-promotion.bat --confirm=ROLLBACK'] as $marker)if(!str_contains($runner,$marker))$errors[]="Target execution runner missing marker [{$marker}].";
    foreach(['nexoraAcquireTargetExecutionLock','nexoraBuildTargetSupportCapsule','latest-support.json','support-capsule.sha256'] as $marker)if(!str_contains($runner,$marker))$errors[]="Target execution support capsule integration missing marker [{$marker}].";
    $support=(string)@file_get_contents($root.'/scripts/lib/target-support-capsule.php');foreach(['env_dump_included','secret_shaped_values_redacted','project_and_home_paths_redacted','step_log_limit_bytes','[REDACTED]'] as $marker)if(!str_contains($support,$marker))$errors[]="Target support capsule missing privacy marker [{$marker}].";
    foreach(['composer update','npm install','migrate:fresh','migrate:reset'] as $forbidden)if(stripos($runner,$forbidden)!==false)$errors[]="Target execution master must delegate protected mutations rather than owning [{$forbidden}].";

    $refresh=(string)@file_get_contents($root.'/scripts/dependency-lock-refresh.php');
    foreach(['--confirm=REFRESH','--no-install','--package-lock-only','review-required','root_lockfiles_mutated','candidate_published'] as $marker)if(!str_contains($refresh,$marker))$errors[]="Dependency lock refresh is missing safety marker [{$marker}].";
    if(!str_contains($refresh,'isolated-candidate-refresh')&&!str_contains($refresh,'double-run-reproducible-candidate-refresh'))$errors[]='Dependency lock refresh must use an isolated candidate mode.';
    if(str_contains($refresh,"nexoraWriteFileReplace($root.'/composer.lock'")||str_contains($refresh,"nexoraWriteFileReplace($root.'/package-lock.json'"))$errors[]='Dependency lock refresh must never promote root lockfiles directly.';
    if(str_contains($refresh,"'scripts/dependency-lock-review.php'")||str_contains($refresh,'reviewed-locks.json'))$errors[]='Dependency lock refresh must never create or invoke reviewed-lock attestation.';
    if(stripos($refresh,'composer install')!==false||stripos($refresh,'npm ci')!==false)$errors[]='Dependency lock refresh must not install the runtime dependency graph.';

    $promote=(string)@file_get_contents($root.'/scripts/dependency-lock-promote.php');
    foreach(['PROMOTE-REVIEWED','dependency-contract-verify.php','--strict-locks','dependency-lock-review.php','--confirm=REVIEWED','nexoraRestoreFileSnapshot','lock-promotion-journal.json'] as $marker)if(!str_contains($promote,$marker))$errors[]="Dependency lock promotion is missing safety marker [{$marker}].";
    $recover=(string)@file_get_contents($root.'/scripts/dependency-lock-promotion-recover.php');
    foreach(['--confirm=ROLLBACK','Durable backup hash mismatch','rolled-back','recovery_verified'] as $marker)if(!str_contains($recover,$marker))$errors[]="Dependency lock promotion recovery is missing safety marker [{$marker}].";

    $review=(string)@file_get_contents($root.'/scripts/dependency-lock-review.php');
    if(!str_contains($review,'nexoraLocateTargetComposer'))$errors[]='Dependency lock review must use trusted target Composer discovery rather than PATH-only lookup.';
    foreach(['--require-refresh-handoff','source_tree_sha256','Lock-refresh handoff lock mismatch'] as $marker)if(!str_contains($review,$marker))$errors[]="Reviewed-lock handoff missing marker [{$marker}].";
    $restart=(string)@file_get_contents($root.'/scripts/target-prerequisite-restart-verify.php');
    foreach(['restart-ticket.json','source_tree_sha256','php_ini_sha256_after','required_extensions'] as $marker)if(!str_contains($restart,$marker))$errors[]="Restart verification missing marker [{$marker}].";
    foreach(['n1-c1-evidence-verify.php','n1-c2-evidence-verify.php','n1-c3-database-matrix-evidence-verify.php','reused-pass','rerun-required'] as $marker)if(!str_contains($runner,$marker))$errors[]="Target execution resume missing marker [{$marker}].";
    if(!str_contains($runner,'$reviewLocks')||!str_contains($runner,'$confirmReview'))$errors[]='Reviewed-lock acceptance must be explicitly guarded by master-runner review flags.';
    $bootstrap=(string)@file_get_contents($root.'/scripts/target-environment-bootstrap.php');
    if(!str_contains($bootstrap,'nexoraLocateTargetComposer'))$errors[]='Target bootstrap must use trusted target Composer discovery rather than PATH-only lookup.';
    foreach(['scripts/n1-c1-dependency-certify.php','scripts/dependency-runtime-verify.php','scripts/dependency-audit.php'] as $file){$src=(string)@file_get_contents($root.'/'.$file);if(!str_contains($src,'nexoraLocateTargetComposer'))$errors[]="Target Composer discovery must be used by [{$file}].";}

    foreach(['scripts/n1-c4-evidence-prepare.php','scripts/n1-c5-evidence-prepare.php'] as $file){$src=(string)@file_get_contents($root.'/'.$file);if(!str_contains($src,'--out='))$errors[]="Deterministic operator-kit output missing in [{$file}].";}
    $release=(string)@file_get_contents($root.'/config/nexora-release.php');if(!str_contains($release,'storage/app/nexora/n1-target-execution/'))$errors[]='Release policy must exclude target execution runtime evidence.';
    $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');if(!str_contains($zero,'storage/app/nexora/n1-target-execution'))$errors[]='Strict zero-state must reject target execution runtime evidence.';


    if(!str_contains($runner,'--plan')||!str_contains($runner,'nexoraBuildN10TargetPlan'))$errors[]='Target execution pack must expose the read-only --plan next-action mode.';
    if(!str_contains($runner,'--prepare-kits requires a real'))$errors[]='Operator-kit preparation must require a real operator name.';

    return ['errors'=>$errors,'warnings'=>[],'metrics'=>[
        'wrappers'=>count(array_filter(['scripts/n1-target-execution.bat','scripts/n1-target-execution.ps1','scripts/n1-target-execution.sh'],fn($f)=>is_file($root.'/'.$f))),
        'lock_refresh_wrappers'=>count(array_filter(['scripts/refresh-dependency-locks.bat','scripts/refresh-dependency-locks.ps1','scripts/refresh-dependency-locks.sh'],fn($f)=>is_file($root.'/'.$f))),
        'lock_promote_wrappers'=>count(array_filter(['scripts/promote-reviewed-dependency-locks.bat','scripts/promote-reviewed-dependency-locks.ps1','scripts/promote-reviewed-dependency-locks.sh'],fn($f)=>is_file($root.'/'.$f))),
        'lock_recovery_wrappers'=>count(array_filter(['scripts/recover-dependency-lock-promotion.bat','scripts/recover-dependency-lock-promotion.ps1','scripts/recover-dependency-lock-promotion.sh'],fn($f)=>is_file($root.'/'.$f))),
        'automated_chunks'=>3,
        'operator_chunks'=>3,
        'ordered_phases'=>count($ordered),
        'automatic_lock_acceptance'=>(str_contains($runner,'--accept')&&!str_contains($runner,'$reviewLocks'))?1:0,
        'direct_destructive_db_calls'=>preg_match('/migrate:fresh|migrate:reset/i',$runner)===1?1:0,
        'trusted_composer_discovery'=>(str_contains($review,'nexoraLocateTargetComposer')&&str_contains($bootstrap,'nexoraLocateTargetComposer'))?1:0,
        'next_action_planner'=>is_file($root.'/scripts/n1-target-next-action.php')?1:0,
        'support_capsule'=>(str_contains($runner,'latest-support.json')&&str_contains($support,'env_dump_included'))?1:0,
        'restart_verification'=>is_file($root.'/scripts/target-prerequisite-restart-verify.php')?1:0,
        'review_handoff'=>str_contains($review,'--require-refresh-handoff')?1:0,
        'resume_chunks'=>substr_count($runner,'resume-c'),
    ]];
}
