<?php

declare(strict_types=1);

/** @return array{errors:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetDiagnosticsContracts(string $root): array
{
    $errors=[];
    $required=[
        'scripts/target-diagnostics.php',
        'scripts/target-diagnostics.bat',
        'scripts/target-diagnostics.ps1',
        'scripts/target-diagnostics.sh',
    ];
    foreach($required as $file){
        if(!is_file($root.'/'.$file) || filesize($root.'/'.$file)===0) $errors[]="missing target diagnostics artifact [{$file}]";
    }

    $runner=is_file($root.'/scripts/target-diagnostics.php')?(string)file_get_contents($root.'/scripts/target-diagnostics.php'):'';
    foreach([
        '--install-deps','--full','certification-preflight.php','package:discover','route:list','schedule:list',
        'nexora:environment:doctor','nexora:filesystem:doctor','nexora:concurrency:doctor','concurrency-contract-verify.php','filesystem-contract-verify.php','dependency-runtime-verify.php','dependency-contract-verify.php','npm ci','npm run typecheck','npm run test','npm run build','performance-build-verify.php','final-closure-status.php',
        'target-diagnostics','summary.json','summary.md','[REDACTED]'
    ] as $marker){
        if(!str_contains($runner,$marker)) $errors[]="target diagnostics runner missing [{$marker}]";
    }
    foreach(['$_ENV','getenv()','phpinfo()'] as $unsafe){
        if(str_contains($runner,$unsafe)) $errors[]="target diagnostics runner must not dump ambient secrets via [{$unsafe}]";
    }

    $bat=is_file($root.'/scripts/target-diagnostics.bat')?(string)file_get_contents($root.'/scripts/target-diagnostics.bat'):'';
    if(!str_contains($bat,'target-diagnostics.php')) $errors[]='target-diagnostics.bat must delegate to target-diagnostics.php';

    return ['errors'=>$errors,'metrics'=>['diagnostic_groups'=>6,'runner_modes'=>2]];
}
