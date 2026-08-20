<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetRuntimeContracts(string $root): array
{
    $errors=[];
    $runner=(string)@file_get_contents($root.'/scripts/target-runtime-run.php');
    $requiredFiles=[
        'scripts/target-runtime-run.php','scripts/target-runtime-run.bat','scripts/target-runtime-run.ps1','scripts/target-runtime-run.sh',
        'scripts/target-runtime-contract-verify.php','scripts/lib/target-runtime-contracts.php',
    ];
    foreach($requiredFiles as $file) if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="missing target runtime artifact [{$file}]";
    foreach([
        'composer.lock','package-lock.json','--install-deps','--full','--keep-going',
        'dependency-contract-verify.php','dependency-runtime-verify.php','npm','typecheck','performance-build-verify.php',
        'package:discover','optimize:clear','route:list','schedule:list','nexora:database:doctor','nexora:environment:doctor',
        'nexora:filesystem:doctor','nexora:transfer:doctor','nexora:runtime:doctor','nexora:concurrency:doctor',
        'certify-release.php','--no-package','final-closure-status.php','first_blocker','[REDACTED]',
    ] as $marker) if(!str_contains($runner,$marker))$errors[]="target runtime runner missing marker [{$marker}]";
    if(str_contains($runner,'migrate:fresh')||str_contains($runner,'migrate:reset'))$errors[]='target runtime runner must delegate destructive migration work to certify-release.php instead of operating on the ambient database';
    if(!str_contains($runner,"if(\$full"))$errors[]='isolated destructive certification must remain explicit --full behavior';
    $bat=(string)@file_get_contents($root.'/scripts/target-runtime-run.bat');
    $ps=(string)@file_get_contents($root.'/scripts/target-runtime-run.ps1');
    $sh=(string)@file_get_contents($root.'/scripts/target-runtime-run.sh');
    foreach([['bat',$bat],['ps1',$ps],['sh',$sh]] as [$kind,$source]) if(!str_contains($source,'target-runtime-run.php'))$errors[]="{$kind} wrapper does not delegate to target-runtime-run.php";
    return ['errors'=>array_values(array_unique($errors)),'metrics'=>['wrappers'=>3,'required_markers'=>26,'destructive_delegates'=>1]];
}
