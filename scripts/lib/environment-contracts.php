<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeEnvironmentContracts(string $root): array
{
    $errors=[]; $warnings=[];
    $required=[
        'config/nexora-environment.php',
        '.env.production.example',
        'app/Nexora/Foundation/Environment/EnvironmentDoctor.php',
        'app/Console/Commands/Nexora/EnvironmentDoctorCommand.php',
        'bootstrap/nexora-installer-bootstrap.php',
        'app/Nexora/Installation/EnvironmentWriter.php',
    ];
    foreach($required as $file){ if(!is_file($root.'/'.$file)||filesize($root.'/'.$file)===0)$errors[]="missing environment/config artifact [{$file}]"; }

    $config=is_file($root.'/config/nexora-environment.php')?(string)file_get_contents($root.'/config/nexora-environment.php'):'';
    foreach(["'required_persisted_keys'","'secret_keys'","'cached_config_path'","NEXORA_ALLOW_INSECURE_HTTP"] as $marker){if(!str_contains($config,$marker))$errors[]="environment policy missing [{$marker}]";}

    $doctor=is_file($root.'/app/Nexora/Foundation/Environment/EnvironmentDoctor.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Environment/EnvironmentDoctor.php'):'';
    foreach(['configurationIsCached','config cache is older','SESSION_ENCRYPT','SESSION_HTTP_ONLY','SESSION_SECURE_COOKIE','APP_DEBUG','APP_ENV=production','environment_mode','app_key_fingerprint'] as $marker){if(!str_contains($doctor,$marker))$errors[]="environment doctor missing [{$marker}]";}
    foreach(['DB_PASSWORD','MAIL_PASSWORD','AWS_SECRET_ACCESS_KEY'] as $secret){ if(str_contains($doctor,"'{$secret}' =>")||str_contains($doctor,'config(\''.$secret))$errors[]="environment doctor must not emit secret [{$secret}]"; }

    $bootstrap=is_file($root.'/bootstrap/nexora-installer-bootstrap.php')?(string)file_get_contents($root.'/bootstrap/nexora-installer-bootstrap.php'):'';
    foreach(['invalid environment active marker','Refusing to fall back to a different environment file','markedSource'] as $marker){if(!str_contains($bootstrap,$marker))$errors[]="installer bootstrap fail-closed environment source contract missing [{$marker}]";}

    $writer=is_file($root.'/app/Nexora/Installation/EnvironmentWriter.php')?(string)file_get_contents($root.'/app/Nexora/Installation/EnvironmentWriter.php'):'';
    foreach(['invalidateCachedConfiguration','bootstrap/cache/config.php','stale Laravel config cache'] as $marker){if(!str_contains($writer,$marker))$errors[]="environment writer config-cache invalidation missing [{$marker}]";}

    $upgrade=is_file($root.'/app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php')?(string)file_get_contents($root.'/app/Nexora/Foundation/Upgrade/UpgradeCompatibilityService.php'):'';
    foreach(['EnvironmentDoctor','environment->inspect(true)',"'environment'=>\$environment"] as $marker){if(!str_contains($upgrade,$marker))$errors[]="upgrade preflight must include environment drift assessment [{$marker}]";}

    $release=is_file($root.'/scripts/build-production-release.php')?(string)file_get_contents($root.'/scripts/build-production-release.php'):'';
    foreach(["'environment' => [",'policy_sha256','nexora:environment:doctor --production','real_environment_packaged'] as $marker){if(!str_contains($release,$marker))$errors[]="production release manifest missing environment policy marker [{$marker}]";}
    $releasePolicy=is_file($root.'/config/nexora-release.php')?(string)file_get_contents($root.'/config/nexora-release.php'):'';
    foreach(["'config/nexora-environment.php'","'.env.production.example'"] as $marker){if(!str_contains($releasePolicy,$marker))$errors[]="production release policy must retain environment artifact [{$marker}]";}

    $production=is_file($root.'/.env.production.example')?(string)file_get_contents($root.'/.env.production.example'):'';
    foreach(['APP_ENV=production','APP_DEBUG=false','APP_KEY=','APP_URL=https://example.com','DB_PASSWORD=','SESSION_ENCRYPT=true','SESSION_SECURE_COOKIE=true','SESSION_HTTP_ONLY=true','SESSION_SAME_SITE=lax','NEXORA_ALLOW_INSECURE_HTTP=false'] as $marker){if(!str_contains($production,$marker))$errors[]="production environment example missing safe marker [{$marker}]";}
    foreach(['APP_KEY=base64:','DB_PASSWORD=root','APP_DEBUG=true','NEXORA_ALLOW_INSECURE_HTTP=true'] as $unsafe){if(str_contains($production,$unsafe))$errors[]="production environment example contains unsafe default [{$unsafe}]";}

    $envExample=is_file($root.'/.env.example')?(string)file_get_contents($root.'/.env.example'):'';
    foreach(['SESSION_ENCRYPT=true','SESSION_HTTP_ONLY=true','SESSION_SECURE_COOKIE=true','QUEUE_FAILED_DRIVER=database-uuids','SESSION_PARTITIONED_COOKIE=false'] as $marker){if(!str_contains($envExample,$marker))$errors[]=".env.example missing current runtime key [{$marker}]";}

    // Laravel config cache only preserves config values. Runtime application code
    // must not call env() outside config/*.php.
    $runtimeEnvCalls=[];
    foreach(['app','routes'] as $base){
        $dir=$root.'/'.$base; if(!is_dir($dir))continue;
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){
            if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
            $tokens=token_get_all((string)file_get_contents($file->getPathname()));
            $count=count($tokens);
            for($i=0;$i<$count;$i++){
                $token=$tokens[$i];
                if(!is_array($token)||$token[0]!==T_STRING||strtolower($token[1])!=='env')continue;
                $j=$i+1; while($j<$count && is_array($tokens[$j]) && in_array($tokens[$j][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true))$j++;
                if(($tokens[$j]??null)==='('){$runtimeEnvCalls[]=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1)).':'.$token[2];}
            }
        }
    }
    foreach($runtimeEnvCalls as $hit)$errors[]="runtime env() call outside config cache boundary [{$hit}]";

    if(is_file($root.'/.env'))$errors[]='source package must not contain a root .env file';

    return ['errors'=>$errors,'warnings'=>$warnings,'metrics'=>[
        'runtime_env_calls'=>count($runtimeEnvCalls),
        'production_template_keys'=>preg_match_all('/^[A-Z0-9_]+=/m',$production),
        'environment_sources'=>2,
        'doctor_commands'=>1,
    ]];
}
