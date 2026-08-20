<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/final-closure.php';

$final=in_array('--final',$argv,true);
$statusOnly=in_array('--status-only',$argv,true);
$install=in_array('--install-deps',$argv,true);
$noPackage=in_array('--no-package',$argv,true);
$keepGoing=in_array('--keep-going',$argv,true);
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');

$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);
if($final) $env['NEXORA_CERT_FINAL_EVIDENCE']='1';
$quote=static fn(string $v):string=>escapeshellarg($v);
$run=static function(string $label,array $parts,bool $required=true) use($root,$env,$quote):bool {
    $cmd=implode(' ',array_map(static fn($p)=>$quote((string)$p),$parts));
    fwrite(STDOUT,"\n[Nexora Final Target] {$label}\n> {$cmd}\n");
    $proc=proc_open($cmd,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($proc)){if($required) fwrite(STDERR,"Unable to start {$label}.\n");return false;}
    $exit=proc_close($proc);
    if($exit!==0 && $required) fwrite(STDERR,"[Nexora Final Target] {$label} failed with exit {$exit}.\n");
    return $exit===0;
};

if($statusOnly){
    $payload=nexoraEvaluateFinalClosure($root);nexoraWriteClosureStatus($root,$payload);
    fwrite(STDOUT,"[Nexora Final Target] {$payload['status']} — {$version}\n");
    exit(($payload['n1_0_done']??false)?0:2);
}

$missing=[];
if(version_compare(PHP_VERSION,'8.3.0','<')) $missing[]='PHP >= 8.3';
if(!class_exists(ZipArchive::class)) $missing[]='PHP ext-zip';
foreach(['composer','node','npm'] as $command){
    $probe=proc_open(escapeshellarg($command).' --version',[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$probePipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($probe)){ $missing[]=$command.' executable'; continue; }
    foreach($probePipes as $pipe) fclose($pipe);
    $probeExit=proc_close($probe);
    if($probeExit!==0) $missing[]=$command.' executable';
}
if($missing!==[]){fwrite(STDERR,"[Nexora Final Target] Missing prerequisites: ".implode(', ',$missing)."\n");exit(1);}

if(!$run('Dependency toolchain + lockfile policy',[PHP_BINARY,'scripts/dependency-runtime-verify.php'])) exit(1);

if($install){
    foreach(['composer.lock','package-lock.json'] as $lock){
        if(!is_file($root.'/'.$lock)){fwrite(STDERR,"[Nexora Final Target] Missing required lockfile {$lock}. Certification never mutates dependency locks. Use the maintainer lock refresh workflow first.\n");exit(1);}
    }
    if(!$run('Composer dependencies',['composer','install','--no-interaction','--prefer-dist','--optimize-autoloader','--no-progress'])) exit(1);
    if(!$run('Node dependencies',['npm','ci','--no-audit','--no-fund'])) exit(1);
}

$cert=[PHP_BINARY,'scripts/certify-release.php'];
if(!$final || $noPackage) $cert[]='--no-package';
if($keepGoing)$cert[]='--keep-going';
if(!$run($final?'Final exact-version certification + packaging':'Automated target certification',$cert)){
    $payload=nexoraEvaluateFinalClosure($root);nexoraWriteClosureStatus($root,$payload);exit(1);
}

$payload=nexoraEvaluateFinalClosure($root);nexoraWriteClosureStatus($root,$payload);
fwrite(STDOUT,"\n[Nexora Final Target] Closure status: {$payload['status']}\n");
foreach($payload['domains'] as $name=>$domain) fwrite(STDOUT,sprintf(" - %-24s %-7s %s\n",$name,strtoupper((string)$domain['status']),(string)$domain['detail']));
if($final && !($payload['n1_0_done']??false)){
    fwrite(STDERR,"[Nexora Final Target] Final mode completed without a sealed production package. N1.0 remains open.\n");exit(2);
}
exit(0);
