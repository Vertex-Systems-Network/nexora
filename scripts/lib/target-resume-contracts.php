<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetResumeContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/target-environment-bootstrap.php','scripts/target-environment-bootstrap.bat','scripts/target-environment-bootstrap.ps1','scripts/target-environment-bootstrap.sh',
        'scripts/target-runtime-evidence-verify.php','scripts/lib/target-resume-contracts.php','scripts/target-resume-contract-verify.php',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0) $errors[]="missing RC23 target bootstrap/resume artifact [{$file}]";
    $bootstrap=(string)@file_get_contents($root.'/scripts/target-environment-bootstrap.php');
    foreach(['php_ini_loaded_file','composer.lock','package-lock.json','ext-','certified range','refresh-dependency-locks.bat','--write'] as $marker) if(!str_contains($bootstrap,$marker)) $errors[]="target bootstrap missing marker [{$marker}]";
    if(preg_match('/https?:\/\//i',$bootstrap)===1) $errors[]='target bootstrap may diagnose tools but must not auto-download executables/dependencies';
    $runner=(string)@file_get_contents($root.'/scripts/target-runtime-run.php');
    foreach(['--resume-latest','--resume=','source_tree_sha256','vendor_installed_sha256','node_modules_lock_sha256','resume_from','target-environment-bootstrap.php'] as $marker) if(!str_contains($runner,$marker)) $errors[]="target runtime resume boundary missing marker [{$marker}]";
    $evidence=(string)@file_get_contents($root.'/scripts/target-runtime-evidence-verify.php');
    foreach(['--input=','--require-pass','--seal','source_tree_sha256','composer_lock_sha256','package_lock_sha256','Unsafe ZIP path','target-runtime-evidence.json'] as $marker) if(!str_contains($evidence,$marker)) $errors[]="target runtime evidence verifier missing marker [{$marker}]";
    $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');
    if(!str_contains($zero,'storage/app/nexora/target-bootstrap')) $errors[]='strict source-zero state must reject generated target-bootstrap evidence';
    $release=(string)@file_get_contents($root.'/config/nexora-release.php');
    if(!str_contains($release,'storage/app/nexora/target-bootstrap/')) $errors[]='production release policy must exclude target-bootstrap evidence';
    foreach(['bat','ps1','sh'] as $kind){
        $file=$root.'/scripts/target-environment-bootstrap.'.$kind;
        if(is_file($file)&&!str_contains((string)file_get_contents($file),'target-environment-bootstrap.php')) $errors[]="{$kind} target bootstrap wrapper must delegate to PHP implementation";
    }
    return ['errors'=>array_values(array_unique($errors)),'metrics'=>['bootstrap_wrappers'=>3,'resume_fingerprints'=>6,'evidence_bindings'=>3]];
}
