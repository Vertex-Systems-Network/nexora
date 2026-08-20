<?php

declare(strict_types=1);
/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetEvidenceContracts(string $root): array
{
    $errors=[];$warnings=[];
    foreach(['scripts/target-evidence-intake.php','scripts/target-evidence-intake.bat','scripts/target-evidence-intake.ps1','scripts/target-evidence-intake.sh','scripts/closure-dashboard.php','scripts/lib/target-evidence-intake.php'] as $relative)if(!is_file($root.'/'.$relative)||filesize($root.'/'.$relative)===0)$errors[]="missing {$relative}";
    $source=(string)@file_get_contents($root.'/scripts/target-evidence-intake.php');$lib=(string)@file_get_contents($root.'/scripts/lib/target-evidence-intake.php');
    foreach(['--input=','--seal','--require-complete','target-evidence-intake.json','reviewed_locks_status'] as $marker)if(!str_contains($source,$marker))$errors[]="target evidence intake missing marker [{$marker}]";
    foreach(['zero_install','upgrade_rehearsal','browser','backup_restore','multi_node_ha','database_matrix','target_runtime','ZipArchive','source_tree_sha256','composer_lock_sha256','package_lock_sha256'] as $marker)if(!str_contains($lib,$marker))$errors[]="target evidence library missing marker [{$marker}]";
    $runtime=(string)@file_get_contents($root.'/scripts/target-runtime-run.php');if(str_contains($runtime,'target-evidence-intake.php'))$warnings[]='target runtime runner invokes evidence intake directly; keep target runtime and operator evidence collection separable.';
    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>['wrappers'=>3,'known_evidence'=>10,'operator_evidence'=>5,'fingerprint_bindings'=>4]];
}
