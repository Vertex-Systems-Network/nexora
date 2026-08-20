<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);
$run=static function(array $command) use($root,$env):array{
    $cmd=implode(' ',array_map('escapeshellarg',$command));
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($proc)) return ['exit_code'=>127,'stdout'=>'','stderr'=>'unable to start process'];
    fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($proc);
    return ['exit_code'=>$exit,'stdout'=>trim($out),'stderr'=>trim($err)];
};
$bootstrap=$run([PHP_BINARY,'scripts/target-environment-bootstrap.php','--json']);
$bootstrapJson=json_decode($bootstrap['stdout'],true);
$lock=$run([PHP_BINARY,'scripts/dependency-lock-review.php','--json']);
$lockJson=json_decode($lock['stdout'],true);
$ini=php_ini_loaded_file()?:null;
$scanned=php_ini_scanned_files()?:null;
$binary=str_replace('\\','/',PHP_BINARY);
$laragonDetected=stripos($binary,'/laragon/')!==false||stripos(str_replace('\\','/',$root),'/laragon/')!==false;
$missingExtensions=[];
foreach((array)($bootstrapJson['extensions']??[]) as $extension) if(!($extension['loaded']??false)) $missingExtensions[]=(string)$extension['name'];
$actions=[];
if($missingExtensions!==[]){
    $actions[]='Enable these extensions in the active php.ini'.($ini?" [{$ini}]":'').': '.implode(', ',$missingExtensions).'. '.($laragonDetected?'Run scripts\\target-prerequisite-remediate.bat first for a safe plan; use --apply-extensions only after reviewing its DLL/backup report, then restart Laragon.':'Restart the active PHP process and open a fresh terminal.');
}
if(!(bool)($bootstrapJson['composer']['available']??false)) $actions[]='Install Composer 2.x or expose the trusted Composer executable on PATH for this terminal; then rerun this intake.';
if(!is_file($root.'/composer.lock')||!is_file($root.'/package-lock.json')) $actions[]='Generate locks only through scripts\\refresh-dependency-locks.bat on a trusted maintainer machine, inspect the diff, then run dependency-lock-review.bat --accept --reviewer=<name> --confirm=REVIEWED.';
elseif(($lockJson['status']??'fail')!=='pass') $actions[]='Resolve lock review errors and explicitly accept the exact hashes with dependency-lock-review.bat --accept --reviewer=<name> --confirm=REVIEWED.';
else $actions[]='Verify reviewed-lock attestation with dependency-lock-review.bat --verify-attestation before target dependency installation.';
$status=($bootstrap['exit_code']===0 && ($lock['exit_code']===0||(!is_file($root.'/composer.lock')&&!is_file($root.'/package-lock.json'))))?'ready':'blocked';
$payload=['schema'=>1,'platform_version'=>$version,'status'=>$status,'checked_at'=>gmdate(DATE_ATOM),'os_family'=>PHP_OS_FAMILY,'laragon_detected'=>$laragonDetected,'project_root'=>$root,'php_binary'=>PHP_BINARY,'php_ini'=>$ini,'php_ini_scanned'=>$scanned,'extension_dir'=>ini_get('extension_dir')?:null,'bootstrap'=>$bootstrapJson,'lock_review'=>$lockJson,'actions'=>$actions];
$dir=$root.'/storage/app/nexora/target-intake';
if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('Unable to create target intake directory.');
file_put_contents($dir.'/latest.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
$md="# Nexora {$version} target prerequisite intake\n\nStatus: **".strtoupper($status)."**\n\n- OS: `".PHP_OS_FAMILY."`\n- Laragon detected: **".($laragonDetected?'yes':'no')."**\n- PHP binary: `".PHP_BINARY."`\n- php.ini: `".($ini??'not loaded')."`\n- extension_dir: `".(ini_get('extension_dir')?:'unknown')."`\n\n## Next actions\n";
foreach($actions as $action) $md.="- {$action}\n";
file_put_contents($dir.'/latest.md',$md);
fwrite(STDOUT,"[Nexora Target Prerequisite Intake] ".strtoupper($status)." — {$version}\nEvidence: storage/app/nexora/target-intake/latest.md\n");
foreach($actions as $action) fwrite(STDOUT," - {$action}\n");
exit($status==='ready'?0:1);
