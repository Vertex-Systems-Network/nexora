<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetIntakeContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/target-prerequisite-intake.php','scripts/target-prerequisite-intake.bat','scripts/target-prerequisite-intake.ps1','scripts/target-prerequisite-intake.sh',
        'scripts/dependency-lock-review.php','scripts/dependency-lock-review.bat','scripts/dependency-lock-review.ps1','scripts/dependency-lock-review.sh',
        'scripts/lib/target-intake-contracts.php','scripts/target-intake-contract-verify.php',
    ];
    foreach($required as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0) $errors[]="missing RC24 target intake artifact [{$file}]";
    $intake=(string)@file_get_contents($root.'/scripts/target-prerequisite-intake.php');
    foreach(['php_ini_loaded_file','php_ini_scanned_files','extension_dir','laragon_detected','dependency-lock-review.php','refresh-dependency-locks.bat','target-intake'] as $marker) if(!str_contains($intake,$marker)) $errors[]="target intake missing marker [{$marker}]";
    if(preg_match('/https?:\/\//i',$intake)===1) $errors[]='target intake must not auto-download tools or dependencies';
    $review=(string)@file_get_contents($root.'/scripts/dependency-lock-review.php');
    foreach(['--accept','--reviewer=','--confirm=','REVIEWED','--verify-attestation','composer validate --strict --check-lock','composer_manifest_sha256','package_manifest_sha256','composer_lock_sha256','package_lock_sha256','npm_integrity_missing'] as $marker) if(!str_contains($review,$marker)) $errors[]="dependency lock review missing marker [{$marker}]";
    $runtime=(string)@file_get_contents($root.'/scripts/target-runtime-run.php');
    if(!str_contains($runtime,"dependency-lock-review.php','--verify-attestation")) $errors[]='target runtime must verify reviewed-lock attestation before locked installation/runtime certification';
    $cert=(string)@file_get_contents($root.'/scripts/certify-release.php');
    if(!str_contains($cert,"dependency-lock-review.php','--verify-attestation")) $errors[]='full certification must verify reviewed-lock attestation';
    $release=(string)@file_get_contents($root.'/config/nexora-release.php');
    foreach(['storage/app/nexora/dependency-intake/','storage/app/nexora/target-intake/'] as $prefix) if(!str_contains($release,$prefix)) $errors[]="production release policy must exclude runtime intake evidence [{$prefix}]";
    $zero=(string)@file_get_contents($root.'/scripts/zero-state-verify.php');
    foreach(['storage/app/nexora/dependency-intake','storage/app/nexora/target-intake'] as $marker) if(!str_contains($zero,$marker)) $errors[]="strict source-zero state must reject generated intake evidence [{$marker}]";
    foreach(['bat','ps1','sh'] as $ext){
        if(!str_contains((string)@file_get_contents($root.'/scripts/target-prerequisite-intake.'.$ext),'target-prerequisite-intake.php')) $errors[]="{$ext} target intake wrapper must delegate to PHP";
        if(!str_contains((string)@file_get_contents($root.'/scripts/dependency-lock-review.'.$ext),'dependency-lock-review.php')) $errors[]="{$ext} lock review wrapper must delegate to PHP";
    }
    return ['errors'=>array_values(array_unique($errors)),'metrics'=>['intake_wrappers'=>3,'lock_review_wrappers'=>3,'attestation_hash_bindings'=>4]];
}
