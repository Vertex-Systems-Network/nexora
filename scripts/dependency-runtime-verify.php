<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$policy=require $root.'/config/nexora-dependencies.php';
require_once $root.'/scripts/lib/target-composer.php';
$errors=[];$observed=['php'=>PHP_VERSION];
if(version_compare(PHP_VERSION,(string)$policy['php']['minimum'],'<')||version_compare(PHP_VERSION,(string)$policy['php']['maximum_exclusive'],'>='))$errors[]='PHP '.PHP_VERSION.' is outside certified range '.$policy['php']['minimum'].' .. <'.$policy['php']['maximum_exclusive'];
foreach(['composer.lock','package-lock.json'] as $lock)if(!is_file($root.'/'.$lock))$errors[]="required lockfile missing [{$lock}]";
$version=static function(array $command) use($root):?string{
    $cmd=implode(' ',array_map('escapeshellarg',$command));
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,null,['bypass_shell'=>false]);
    if(!is_resource($proc))return null;fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($proc);return $exit===0?trim($out.' '.$err):null;
};
$composerTool=nexoraLocateTargetComposer($root);$composer=(bool)($composerTool['available']??false)?(string)($composerTool['raw']??('Composer version '.($composerTool['version']??''))):null;$node=$version(['node','--version']);$npm=$version(['npm','--version']);
$observed['composer']=$composer;$observed['node']=$node;$observed['npm']=$npm;
if($composer===null)$errors[]='Composer executable unavailable';elseif(preg_match('/Composer version\s+(\d+\.\d+\.\d+)/i',$composer,$m)){if(version_compare($m[1],(string)$policy['composer']['minimum'],'<')||version_compare($m[1],(string)$policy['composer']['maximum_exclusive'],'>='))$errors[]="Composer {$m[1]} outside certified range";}else $errors[]='Unable to parse Composer version';
if($node===null)$errors[]='Node executable unavailable';elseif(preg_match('/v?(\d+)\./',$node,$m)){if((int)$m[1]<(int)$policy['node']['minimum_major']||(int)$m[1]>=(int)$policy['node']['maximum_major_exclusive'])$errors[]="Node major {$m[1]} outside certified range";}else $errors[]='Unable to parse Node version';
if($npm===null)$errors[]='npm executable unavailable';elseif(preg_match('/(\d+)\./',$npm,$m)){if((int)$m[1]<(int)$policy['npm']['minimum_major']||(int)$m[1]>=(int)$policy['npm']['maximum_major_exclusive'])$errors[]="npm major {$m[1]} outside certified range";}else $errors[]='Unable to parse npm version';
$package=json_decode((string)file_get_contents($root.'/package.json'),true);$packageManager=(string)($package['packageManager']??'');
if($npm!==null && preg_match('/^(?:npm@)(\d+\.\d+\.\d+)$/',$packageManager,$pm)===1 && preg_match('/(\d+\.\d+\.\d+)/',$npm,$nv)===1 && $pm[1]!==$nv[1])$errors[]="npm {$nv[1]} does not match pinned packageManager {$packageManager}";
if($errors!==[]){fwrite(STDERR,"[Nexora Dependency Runtime] FAILED\n - ".implode("\n - ",$errors)."\n");exit(1);} 
fwrite(STDOUT,"[Nexora Dependency Runtime] PASS — PHP {$observed['php']}; {$observed['node']}; npm {$observed['npm']}; Composer available; lockfiles present.\n");
