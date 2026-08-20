<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeHaFinalContracts(string $root): array
{
    $errors=[];
    $required=[
        'config/nexora-ha.php',
        'app/Nexora/Cloud/Services/HaReadinessService.php',
        'app/Nexora/Cloud/Services/ClusterRehearsalService.php',
        'app/Nexora/Cloud/Services/BackupRestoreRehearsalService.php',
        'scripts/ha-evidence-verify.php',
        'scripts/backup-restore-evidence-verify.php',
        'scripts/final-evidence-verify.php',
        'docs/ha-certification-evidence.example.json',
        'docs/backup-restore-evidence.example.json',
        'docs/zero-install-evidence.example.json',
        'docs/upgrade-rehearsal-evidence.example.json',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)) $errors[]="missing RC10 contract file [{$file}]";

    $console=is_file($root.'/routes/console.php')?(string)file_get_contents($root.'/routes/console.php'):'';
    foreach(['nexora:ha:status','nexora:ha:rehearse','nexora:backup:rehearse'] as $command) if(!str_contains($console,$command)) $errors[]="missing RC10 operator command [{$command}]";

    $runner=is_file($root.'/scripts/certify-release.php')?(string)file_get_contents($root.'/scripts/certify-release.php'):'';
    if(!str_contains($runner,'ha-final-contract-verify.php')) $errors[]='certification runner must execute the RC10 HA/final-evidence source contract';
    if(!str_contains($runner,'NEXORA_CERT_FINAL_EVIDENCE')) $errors[]='certification runner must expose an explicit final operator-evidence gate';
    if(!str_contains($runner,'final-evidence-verify.php')) $errors[]='certification runner must aggregate final evidence before production packaging';
    $finalEvidence=is_file($root.'/scripts/final-evidence-verify.php')?(string)file_get_contents($root.'/scripts/final-evidence-verify.php'):'';
    foreach(['target-evidence-intake.json','nexoraValidateTargetEvidenceIntakeManifest'] as $marker) if(!str_contains($finalEvidence,$marker)) $errors[]="final evidence must require RC25 target evidence intake marker [{$marker}]";

    $builder=is_file($root.'/scripts/build-production-release.php')?(string)file_get_contents($root.'/scripts/build-production-release.php'):'';
    if(!str_contains($builder,'final-evidence.json')) $errors[]='production builder must require final-evidence.json';
    if(!str_contains($builder,'final_evidence_report_sha256')) $errors[]='production release manifest must seal the final-evidence report digest';

    $topology=is_file($root.'/app/Nexora/Cloud/Services/HaReadinessService.php')?(string)file_get_contents($root.'/app/Nexora/Cloud/Services/HaReadinessService.php'):'';
    foreach(['shared_cache','shared_session','async_queue','shared_object_storage','fresh_active_nodes','node_version_consistency','scheduler_leader'] as $check) if(!str_contains($topology,"'{$check}'")) $errors[]="HA readiness service missing check [{$check}]";

    $backupTemplate=is_file($root.'/docs/backup-restore-evidence.example.json')?(string)file_get_contents($root.'/docs/backup-restore-evidence.example.json'):'';
    $haTemplate=is_file($root.'/docs/ha-certification-evidence.example.json')?(string)file_get_contents($root.'/docs/ha-certification-evidence.example.json'):'';
    if(!str_contains($backupTemplate,'"restore_to_disposable_target": "fail"')) $errors[]='backup/restore evidence template must be fail-closed';
    if(!str_contains($haTemplate,'"scheduler_failover": "fail"')) $errors[]='HA evidence template must be fail-closed';

    return ['ok'=>$errors===[],'errors'=>$errors,'metrics'=>['required_files'=>count($required),'operator_commands'=>3,'ha_checks'=>7,'manual_evidence_domains'=>7]];
}
