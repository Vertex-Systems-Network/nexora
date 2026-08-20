<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/trusted-update.php';
$target=null;$apply=false;$confirm=null;foreach(array_slice($argv,1) as $a){if(str_starts_with($a,'--target='))$target=substr($a,9);elseif($a==='--apply')$apply=true;elseif(str_starts_with($a,'--confirm='))$confirm=substr($a,10);}
if(!$target){fwrite(STDERR,"Usage: php scripts/trusted-update-cleanup.php --target=<managed-stage-dir> [--apply --confirm=CLEAN]\n");exit(2);}
$config=require $root.'/config/nexora-update-trust.php';$managed=(string)($config['managed_staging_root']??$root.'/storage/app/nexora/update-trust/staging');$managedNorm=str_replace('\\','/',rtrim($managed,'/\\')).'/';$targetNorm=str_replace('\\','/',rtrim($target,'/\\'));
if(!str_starts_with(strtolower($targetNorm.'/'),strtolower($managedNorm))){fwrite(STDERR,"[Nexora Trusted Update Cleanup] REFUSED — target must be inside managed_staging_root.\n");exit(2);}
$record=nexoraUpdateTrustReadJson(nexoraUpdateStageRecordPath($root,$target));if(!is_array($record)){fwrite(STDERR,"[Nexora Trusted Update Cleanup] REFUSED — matching stage record missing.\n");exit(2);}
$status=(string)($record['status']??'');if(!in_array($status,['quarantined','verified'],true)){fwrite(STDERR,"[Nexora Trusted Update Cleanup] REFUSED — stage record status is not cleanup-eligible.\n");exit(2);}
$stamp=strtotime((string)($record['updated_at']??''));$ttl=max(1,(int)($config['quarantine_ttl_hours']??168));$old=$stamp!==false&&$stamp<=time()-$ttl*3600;
$payload=['target'=>$target,'status'=>$status,'older_than_ttl'=>$old,'ttl_hours'=>$ttl,'apply'=>$apply];fwrite(STDOUT,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
if(!$apply)exit(0);if($confirm!=='CLEAN'){fwrite(STDERR,"[Nexora Trusted Update Cleanup] REFUSED — destructive cleanup requires --confirm=CLEAN.\n");exit(2);}if(!$old){fwrite(STDERR,"[Nexora Trusted Update Cleanup] REFUSED — stage is newer than configured cleanup TTL.\n");exit(2);}
$remove=function(string $path)use(&$remove):void{if(is_link($path)||is_file($path)){if(!@unlink($path))throw new RuntimeException('Unable to delete staged file.');return;}if(!is_dir($path))return;foreach(scandir($path)?:[] as $n){if($n==='.'||$n==='..')continue;$remove($path.DIRECTORY_SEPARATOR.$n);}if(!@rmdir($path))throw new RuntimeException('Unable to remove staged directory.');};
try{$remove($target);$rp=nexoraUpdateStageRecordPath($root,$target);if(is_file($rp)&&!@unlink($rp))throw new RuntimeException('Unable to clear stage record.');fwrite(STDOUT,"[Nexora Trusted Update Cleanup] PASS — managed staged/quarantined target removed.\n");exit(0);}catch(Throwable $e){fwrite(STDERR,"[Nexora Trusted Update Cleanup] FAIL — {$e->getMessage()}\n");exit(1);}
