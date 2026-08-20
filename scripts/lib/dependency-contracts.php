<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int|string|bool>} */
function nexoraAnalyzeDependencyContracts(string $root, bool $strictLocks = false): array
{
    $errors=[]; $warnings=[];
    foreach(['composer.json','package.json','config/nexora-dependencies.php','config/nexora-release.php','config/nexora-framework.php'] as $file){
        if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="missing dependency artifact [{$file}]";
    }
    $composer=[];$package=[];
    try{$composer=json_decode((string)file_get_contents($root.'/composer.json'),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$errors[]='composer.json invalid: '.$e->getMessage();}
    try{$package=json_decode((string)file_get_contents($root.'/package.json'),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$errors[]='package.json invalid: '.$e->getMessage();}

    $policy=is_file($root.'/config/nexora-dependencies.php')?require $root.'/config/nexora-dependencies.php':[];
    foreach(['php','composer','node','npm','lockfiles','deterministic_install','audit'] as $key) if(!array_key_exists($key,$policy))$errors[]="dependency policy missing [{$key}]";

    if(($composer['require']['php']??null)!=='>=8.3 <8.5')$errors[]='composer PHP constraint must match certified range >=8.3 <8.5';
    if(($composer['require']['laravel/framework']??null)!=='^13.24')$errors[]='laravel/framework must remain ^13.24 so reviewed Laravel 13.24+ minor/patch updates are permitted without crossing into Laravel 14';
    $frameworkPolicy=is_file($root.'/config/nexora-framework.php')?(string)file_get_contents($root.'/config/nexora-framework.php'):'';
    foreach(["'minimum' => '13.24.0'","'maximum_exclusive' => '14.0.0'","'composer_constraint' => '^13.24'"] as $marker){
        if(!str_contains($frameworkPolicy,$marker))$errors[]="framework compatibility policy missing [{$marker}]";
    }
    if(($composer['config']['sort-packages']??false)!==true)$errors[]='Composer sort-packages must remain enabled';
    if(($composer['config']['preferred-install']??null)!=='dist')$errors[]='Composer preferred-install must remain dist';

    foreach(['require','require-dev'] as $bucket){
        foreach((array)($composer[$bucket]??[]) as $name=>$constraint){
            if(str_starts_with((string)$name,'ext-'))continue;
            $constraint=trim((string)$constraint);
            if($constraint===''||$constraint==='*'||preg_match('/(?:dev-|@dev|master|main|trunk|https?:\/\/|git\+)/i',$constraint)===1)$errors[]="unbounded/non-release Composer constraint [{$bucket}.{$name}={$constraint}]";
        }
    }

    foreach(['dependencies','devDependencies'] as $bucket){
        foreach((array)($package[$bucket]??[]) as $name=>$constraint){
            $constraint=trim((string)$constraint);
            if($constraint===''||$constraint==='*'||strtolower($constraint)==='latest'||preg_match('/^(?:git|https?|file|workspace):/i',$constraint)===1)$errors[]="unbounded/non-registry npm constraint [{$bucket}.{$name}={$constraint}]";
        }
    }

    $engines=$package['engines']??[];
    if(!isset($engines['node'],$engines['npm']))$errors[]='package.json must declare Node and npm engines';
    if(($engines['node']??null)!=='>=22 <25')$errors[]='package.json Node engine must match certified range >=22 <25';
    if(($engines['npm']??null)!=='>=10 <11')$errors[]='package.json npm engine must match certified range >=10 <11';
    if(!is_string($package['packageManager']??null)||!str_starts_with((string)$package['packageManager'],'npm@'))$errors[]='package.json must pin packageManager to npm@<version>';

    $composerLock=is_file($root.'/composer.lock');
    $npmLock=is_file($root.'/package-lock.json');
    if(!$composerLock){if($strictLocks)$errors[]='composer.lock missing; deterministic Composer certification is blocked';else $warnings[]='composer.lock missing; deterministic Composer certification is blocked';}
    if(!$npmLock){if($strictLocks)$errors[]='package-lock.json missing; npm ci certification is blocked';else $warnings[]='package-lock.json missing; npm ci certification is blocked';}

    if($composerLock){
        try{$lock=json_decode((string)file_get_contents($root.'/composer.lock'),true,512,JSON_THROW_ON_ERROR);if(!isset($lock['content-hash'],$lock['packages'],$lock['packages-dev']))$errors[]='composer.lock missing required Composer lock metadata';}catch(Throwable $e){$errors[]='composer.lock invalid: '.$e->getMessage();}
    }
    if($npmLock){
        try{$lock=json_decode((string)file_get_contents($root.'/package-lock.json'),true,512,JSON_THROW_ON_ERROR);if((int)($lock['lockfileVersion']??0)<3)$errors[]='package-lock.json lockfileVersion must be >= 3';if(!isset($lock['packages']['']))$errors[]='package-lock.json missing root package metadata';}catch(Throwable $e){$errors[]='package-lock.json invalid: '.$e->getMessage();}
    }

    $final=is_file($root.'/scripts/final-target-run.php')?(string)file_get_contents($root.'/scripts/final-target-run.php'):'';
    foreach(["composer.lock","package-lock.json","npm','ci","--no-audit","--no-fund"] as $marker)if(!str_contains($final,$marker))$errors[]="final target runner missing deterministic dependency marker [{$marker}]";
    if(str_contains($final,"['npm','install'"))$errors[]='final target certification must never fall back to npm install when package-lock.json is absent';

    $cert=is_file($root.'/scripts/certify-release.php')?(string)file_get_contents($root.'/scripts/certify-release.php'):'';
    foreach(['dependency-contract','dependency-provenance','dependency-audit','composer.lock','package-lock.json','composer','audit','npm','audit'] as $marker)if(!str_contains($cert,$marker))$errors[]="certification runner missing dependency/supply-chain gate marker [{$marker}]";

    foreach(['scripts/refresh-dependency-locks.bat','scripts/refresh-dependency-locks.ps1','scripts/refresh-dependency-locks.sh'] as $refreshFile){if(!is_file($root.'/'.$refreshFile)||filesize($root.'/'.$refreshFile)===0)$errors[]="missing maintainer lock refresh wrapper [{$refreshFile}]";}

    foreach(['scripts/bootstrap-installer.bat','scripts/bootstrap-installer.ps1','scripts/bootstrap-installer.sh'] as $bootstrapFile){
        $bootstrap=is_file($root.'/'.$bootstrapFile)?(string)file_get_contents($root.'/'.$bootstrapFile):'';
        if(!str_contains($bootstrap,'composer.lock')||!str_contains($bootstrap,'package-lock.json'))$errors[]="source bootstrap must require committed lockfiles [{$bootstrapFile}]";
        if(!str_contains($bootstrap,'npm ci'))$errors[]="source bootstrap must use npm ci [{$bootstrapFile}]";
    }
    $publicBootstrap=is_file($root.'/public/nexora-bootstrap.php')?(string)file_get_contents($root.'/public/nexora-bootstrap.php'):'';
    foreach(['composer.lock is required for deterministic PHP dependency installation','package-lock.json is required for deterministic frontend dependency installation',"\$npmArgs = 'ci --no-audit --no-fund'"] as $marker)if(!str_contains($publicBootstrap,$marker))$errors[]="deployment bootstrap missing deterministic dependency guard [{$marker}]";

    $runtime=is_file($root.'/scripts/dependency-runtime-verify.php')?(string)file_get_contents($root.'/scripts/dependency-runtime-verify.php'):'';
    foreach(['composer','node','npm','required lockfile missing'] as $marker)if(!str_contains($runtime,$marker))$errors[]="dependency runtime verifier missing [{$marker}]";

    $builder=is_file($root.'/scripts/build-production-release.php')?(string)file_get_contents($root.'/scripts/build-production-release.php'):'';
    foreach(['dependency-audit.json','dependency-provenance.json','dependency_audit_report_sha256','dependency_provenance_report_sha256','lockfiles_required'] as $marker)if(!str_contains($builder,$marker))$errors[]="production builder missing dependency provenance marker [{$marker}]";

    $release=is_file($root.'/config/nexora-release.php')?(string)file_get_contents($root.'/config/nexora-release.php'):'';
    foreach(["'composer.lock'","'package-lock.json'"] as $marker)if(!str_contains($release,$marker))$errors[]="production release policy missing lockfile [{$marker}]";

    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>[
        'composer_lock'=>$composerLock,'npm_lock'=>$npmLock,
        'direct_prod_dependencies'=>count($composer['require']??[])+count($package['dependencies']??[]),
        'direct_dev_dependencies'=>count($composer['require-dev']??[])+count($package['devDependencies']??[]),
        'strict_locks'=>$strictLocks,
        'laravel_major'=>13,
        'laravel_minimum_minor'=>24,
    ]];
}
