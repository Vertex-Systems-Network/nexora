<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeFinalClosureContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/lib/final-closure.php',
        'scripts/final-closure-status.php',
        'scripts/final-target-run.php',
        'scripts/final-target-run.bat',
        'scripts/final-target-run.ps1',
        'scripts/final-target-run.sh',
        'scripts/zero-install-evidence-verify.php',
        'scripts/upgrade-rehearsal-evidence-verify.php',
        'scripts/release-artifact-verify.php',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file) || filesize($root.'/'.$file)===0) $errors[]="missing final closure artifact [{$file}]";
    $runner=is_file($root.'/scripts/final-target-run.php')?(string)file_get_contents($root.'/scripts/final-target-run.php'):'';
    foreach(['--final','--status-only','--install-deps','NEXORA_CERT_FINAL_EVIDENCE','certify-release.php','nexoraEvaluateFinalClosure'] as $marker) if(!str_contains($runner,$marker)) $errors[]="final target runner missing [{$marker}]";
    $closure=is_file($root.'/scripts/lib/final-closure.php')?(string)file_get_contents($root.'/scripts/lib/final-closure.php'):'';
    foreach(['automated_certification','build_assets','http_performance','database_matrix','zero_install','upgrade_rehearsal','browser','backup_restore','multi_node_ha','final_evidence','production_package','blocking_domains','n1_0_done'] as $domain) if(!str_contains($closure,$domain)) $errors[]="final closure domain missing [{$domain}]";
    $quality=is_file($root.'/scripts/quality-check.bat')?(string)file_get_contents($root.'/scripts/quality-check.bat'):'';
    if(!str_contains($quality,'certify-release.php')) $errors[]='quality-check.bat must retain the single core certification runner';
    return ['errors'=>$errors,'metrics'=>['closure_domains'=>11,'runner_modes'=>3]];
}
