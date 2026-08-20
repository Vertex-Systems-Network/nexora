<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';

$platform=require $root.'/config/nexora.php';
$policy=require $root.'/config/nexora-dependencies.php';
$version=(string)($platform['version']??'unknown');
$write=in_array('--write',$argv,true);
$jsonOnly=in_array('--json',$argv,true);

$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);
$commandVersion=static function(string $command,array $args=['--version']) use($root,$env):array{
    $parts=array_merge([$command],$args);
    $cmd=implode(' ',array_map(static fn(string $v):string=>escapeshellarg($v),$parts));
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($proc)) return ['available'=>false,'version'=>null,'raw'=>null];
    fclose($pipes[0]);
    $stdout=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);
    $stderr=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);
    $exit=proc_close($proc);
    $raw=trim($stdout!==''?$stdout:$stderr);
    preg_match('/(\d+\.\d+(?:\.\d+)?)/',$raw,$m);
    return ['available'=>$exit===0,'version'=>$m[1]??null,'raw'=>$raw!==''?$raw:null];
};
$versionInRange=static function(?string $value,string $min,string $max):bool{
    return is_string($value)&&version_compare($value,$min,'>=')&&version_compare($value,$max,'<');
};
$majorInRange=static function(?string $value,int $min,int $max):bool{
    if(!is_string($value)||!preg_match('/^(\d+)/',$value,$m)) return false;
    $major=(int)$m[1]; return $major>=$min&&$major<$max;
};

$composer=nexoraLocateTargetComposer($root);
$node=$commandVersion('node',['--version']);
$npm=$commandVersion('npm',['--version']);
$composerManifest=json_decode((string)file_get_contents($root.'/composer.json'),true,512,JSON_THROW_ON_ERROR);
$extensions=[];
foreach((array)($composerManifest['require']??[]) as $name=>$constraint){
    if(!str_starts_with((string)$name,'ext-')) continue;
    $extension=substr((string)$name,4);
    $extensions[]=['name'=>$extension,'loaded'=>extension_loaded($extension)];
}
usort($extensions,static fn(array $a,array $b):int=>strcmp($a['name'],$b['name']));

$checks=[];
$add=static function(string $id,bool $ok,string $message,string $action='') use(&$checks):void{
    $checks[]=['id'=>$id,'ok'=>$ok,'message'=>$message,'action'=>$action];
};
$add('php.range',$versionInRange(PHP_VERSION,(string)$policy['php']['minimum'],(string)$policy['php']['maximum_exclusive']),
    'PHP '.PHP_VERSION.'; certified range '.$policy['php']['minimum'].' - <'.$policy['php']['maximum_exclusive'].'.',
    'Select a Laragon PHP build inside the certified range.');
foreach($extensions as $extension){
    $add('php.ext.'.$extension['name'],$extension['loaded'],'PHP extension '.$extension['name'].': '.($extension['loaded']?'loaded':'missing'),
        'Enable extension='.$extension['name'].' in the active php.ini and restart the Laragon terminal/web server.');
}
$add('composer.available',$composer['available'],'Composer: '.($composer['raw']??'not found'),
    'Install Composer 2.x or place a trusted Composer binary/phar under Laragon bin/composer; Nexora may invoke a discovered Laragon candidate directly without mutating global PATH.');
$add('composer.range',$composer['available']&&$versionInRange($composer['version'],(string)$policy['composer']['minimum'],(string)$policy['composer']['maximum_exclusive']),
    'Composer version '.($composer['version']??'unavailable').'; certified range '.$policy['composer']['minimum'].' - <'.$policy['composer']['maximum_exclusive'].'.',
    'Use a Composer version inside the certified range.');
$add('node.range',$node['available']&&$majorInRange($node['version'],(int)$policy['node']['minimum_major'],(int)$policy['node']['maximum_major_exclusive']),
    'Node '.($node['version']??'not found').'; certified majors '.$policy['node']['minimum_major'].' - <'.$policy['node']['maximum_major_exclusive'].'.',
    'Install/select a certified Node major.');
$add('npm.range',$npm['available']&&$majorInRange($npm['version'],(int)$policy['npm']['minimum_major'],(int)$policy['npm']['maximum_major_exclusive']),
    'npm '.($npm['version']??'not found').'; certified majors '.$policy['npm']['minimum_major'].' - <'.$policy['npm']['maximum_major_exclusive'].'.',
    'Use the npm major declared by packageManager/engines.');

$composerLock=$root.'/'.(string)$policy['lockfiles']['composer'];
$npmLock=$root.'/'.(string)$policy['lockfiles']['npm'];
$add('lock.composer',is_file($composerLock),'composer.lock: '.(is_file($composerLock)?'present':'missing'),
    'On a trusted maintainer machine run scripts\\refresh-dependency-locks.bat, review the diff, then commit the lockfile.');
$add('lock.npm',is_file($npmLock),'package-lock.json: '.(is_file($npmLock)?'present':'missing'),
    'On a trusted maintainer machine run scripts\\refresh-dependency-locks.bat, review the diff, then commit the lockfile.');

$ok=!in_array(false,array_column($checks,'ok'),true);
$payload=[
    'schema'=>1,'platform_version'=>$version,'status'=>$ok?'ready':'blocked','checked_at'=>gmdate(DATE_ATOM),
    'os_family'=>PHP_OS_FAMILY,'laragon_detected'=>(stripos(str_replace('\\','/',PHP_BINARY),'/laragon/')!==false || stripos(str_replace('\\','/',$root),'/laragon/')!==false),'php_binary'=>PHP_BINARY,'php_ini'=>php_ini_loaded_file()?:null,'php_ini_scanned'=>php_ini_scanned_files()?:null,'extension_dir'=>ini_get('extension_dir')?:null,
    'composer'=>$composer,'node'=>$node,'npm'=>$npm,'extensions'=>$extensions,
    'composer_lock_sha256'=>is_file($composerLock)?(hash_file('sha256',$composerLock)?:null):null,
    'package_lock_sha256'=>is_file($npmLock)?(hash_file('sha256',$npmLock)?:null):null,
    'checks'=>$checks,
];

if($write){
    $dir=$root.'/storage/app/nexora/target-bootstrap';
    if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('Unable to create target bootstrap evidence directory.');
    file_put_contents($dir.'/latest.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
    $md="# Nexora {$version} target bootstrap\n\nStatus: **".strtoupper($payload['status'])."**\n\n| Check | Status | Detail | Action |\n|---|---:|---|---|\n";
    foreach($checks as $check) $md.='| '.$check['id'].' | '.($check['ok']?'PASS':'FAIL').' | '.str_replace('|','\\|',$check['message']).' | '.str_replace('|','\\|',$check['action'])." |\n";
    file_put_contents($dir.'/latest.md',$md);
}

if($jsonOnly){
    fwrite(STDOUT,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
}else{
    fwrite(STDOUT,"[Nexora Target Bootstrap] ".strtoupper($payload['status'])." — {$version}\n");
    foreach($checks as $check) fwrite(STDOUT,sprintf("[%s] %s\n",$check['ok']?'PASS':'FAIL',$check['message']));
    if(!$ok){
        fwrite(STDOUT,"\nRequired actions:\n");
        foreach($checks as $check) if(!$check['ok']&&$check['action']!=='') fwrite(STDOUT," - {$check['action']}\n");
    }
    if($write) fwrite(STDOUT,"Evidence: storage/app/nexora/target-bootstrap/latest.json\n");
}
exit($ok?0:1);
