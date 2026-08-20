<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';
foreach(['composer.lock','package-lock.json','vendor/autoload.php'] as $required){if(!file_exists($root.'/'.$required)){fwrite(STDERR,"[Nexora Dependency Audit] Missing {$required}.\n");exit(1);}}
if(!is_dir($root.'/node_modules')){fwrite(STDERR,"[Nexora Dependency Audit] Missing node_modules. Run npm ci first.\n");exit(1);}
$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);
$run=static function(array $parts) use($root,$env):int{$cmd=implode(' ',array_map('escapeshellarg',$parts));passthru($cmd,$exit);return $exit;};
$composerTool=nexoraLocateTargetComposer($root);$composerCommand=(array)($composerTool['command']??[]);if($composerCommand===[]){fwrite(STDERR,"[Nexora Dependency Audit] Trusted Composer executable unavailable.\n");exit(1);}if($run(array_merge($composerCommand,['audit','--locked','--no-interaction']))!==0)exit(1);
if($run(['npm','audit','--audit-level=high'])!==0)exit(1);
$dir=$root.'/storage/app/nexora/certification';if(!is_dir($dir))mkdir($dir,0775,true);
$payload=['schema'=>1,'status'=>'pass','platform_version'=>(require $root.'/config/nexora.php')['version'],'completed_at'=>gmdate(DATE_ATOM),'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json')];
file_put_contents($dir.'/dependency-audit.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
fwrite(STDOUT,"[Nexora Dependency Audit] PASS\n");
