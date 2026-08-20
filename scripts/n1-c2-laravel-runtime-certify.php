<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$statusOnly=in_array('--status-only',$argv,true);
$dbConnection=strtolower((string)(getenv('NEXORA_CERT_DB_CONNECTION')?:'mysql'));
$dbDatabase=(string)(getenv('NEXORA_CERT_DB_DATABASE')?:($dbConnection==='sqlite'?$root.'/storage/app/nexora/certification/c2-sqlite.sqlite':'nexora_certification'));
foreach($argv as $arg){
 if(str_starts_with($arg,'--db-connection='))$dbConnection=strtolower(trim(substr($arg,16)));
 if(str_starts_with($arg,'--db-database='))$dbDatabase=trim(substr($arg,14));
}
$allowed=['mysql','mariadb','pgsql','sqlite','sqlsrv'];
if(!in_array($dbConnection,$allowed,true)){fwrite(STDERR,"[N1.0-C2] Unsupported DB connection [{$dbConnection}].\n");exit(2);}
if($dbConnection==='sqlite'){
 $safePrefix=str_replace('\\','/',$root.'/storage/app/nexora/certification/');
 if(!str_starts_with(str_replace('\\','/',$dbDatabase),$safePrefix)){fwrite(STDERR,"[N1.0-C2] SQLite certification DB must live under storage/app/nexora/certification/.\n");exit(2);}
}elseif(preg_match('/^nexora[_-](?:test|testing|cert|certification)[A-Za-z0-9_-]*$/i',$dbDatabase)!==1){
 fwrite(STDERR,"[N1.0-C2] Refusing destructive certification against unsafe database [{$dbDatabase}].\n");exit(2);
}
$base=$root.'/storage/app/nexora/n1-c2';
if($statusOnly){$latest=$base.'/latest.json';if(is_file($latest)){fwrite(STDOUT,(string)file_get_contents($latest));exit(0);}fwrite(STDOUT,"[N1.0-C2] No prior C2 evidence.\n");exit(2);}
$runId=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));$dir=$base.'/'.$runId;$logs=$dir.'/steps';
foreach([$base,$dir,$logs] as $d)if(!is_dir($d)&&!mkdir($d,0775,true)&&!is_dir($d))throw new RuntimeException("Unable to create {$d}");
$source=nexoraComputeSourceAttestation($root);
$env=NexoraBootstrapProcessEnvironment::build($root,[
 'APP_ENV'=>'testing','APP_DEBUG'=>'false','NEXORA_INSTALLER_BYPASS'=>'true','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','QUEUE_CONNECTION'=>'sync',
 'DB_CONNECTION'=>$dbConnection,
 'DB_HOST'=>(string)(getenv('NEXORA_CERT_DB_HOST')?:getenv('DB_HOST')?:'127.0.0.1'),
 'DB_PORT'=>(string)(getenv('NEXORA_CERT_DB_PORT')?:getenv('DB_PORT')?:match($dbConnection){'pgsql'=>'5432','sqlsrv'=>'1433',default=>'3306'}),
 'DB_DATABASE'=>$dbDatabase,
 'DB_USERNAME'=>(string)(getenv('NEXORA_CERT_DB_USERNAME')?:getenv('DB_USERNAME')?:match($dbConnection){'pgsql'=>'postgres','sqlsrv'=>'sa',default=>'root'}),
 'DB_PASSWORD'=>(($p=getenv('NEXORA_CERT_DB_PASSWORD'))!==false?(string)$p:(($p=getenv('DB_PASSWORD'))!==false?(string)$p:($dbConnection==='mysql'||$dbConnection==='mariadb'?'root':''))),
 'NEXORA_CERT_EXPECT_DB_CONNECTION'=>$dbConnection,
 'NEXORA_CERT_EXPECT_DB_DATABASE'=>$dbDatabase,
]);
$steps=[];$first=null;$status='pass';
$redact=static function(string $text):string{foreach(['/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i','/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i'] as $p)$text=(string)preg_replace($p,'$1[REDACTED]',$text);return $text;};
$run=static function(string $id,string $label,array $parts)use($root,$env,$logs,$redact,&$steps,&$first,&$status):bool{
 $cmd=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$parts));fwrite(STDOUT,"\n[N1.0-C2] {$label}\n> {$cmd}\n");$started=microtime(true);
 $p=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
 if(!is_resource($p)){$out='';$err='Unable to start process.';$exit=127;}else{fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($p);} 
 $out=$redact($out);$err=$redact($err);file_put_contents("{$logs}/{$id}.stdout.log",$out);file_put_contents("{$logs}/{$id}.stderr.log",$err);
 if($out!=='')fwrite(STDOUT,$out.(str_ends_with($out,"\n")?'':"\n"));if($err!=='')fwrite(STDERR,$err.(str_ends_with($err,"\n")?'':"\n"));
 $ok=$exit===0;$steps[]=['id'=>$id,'label'=>$label,'status'=>$ok?'pass':'fail','required'=>true,'exit_code'=>$exit,'duration_seconds'=>round(microtime(true)-$started,3),'stdout_log'=>"steps/{$id}.stdout.log",'stderr_log'=>"steps/{$id}.stderr.log"];
 if(!$ok){$status='blocked';if($first===null)$first=['id'=>$id,'label'=>$label,'exit_code'=>$exit];}return $ok;
};
$ordered=[
 ['c1-evidence','Dependency-backed C1 PASS evidence',[PHP_BINARY,'scripts/n1-c1-evidence-verify.php']],
 ['installed-dependencies','Installed dependency graph still matches reviewed locks',[PHP_BINARY,'scripts/n1-c1-installed-dependency-verify.php']],
 ['package-discover','Laravel package discovery',[PHP_BINARY,'artisan','package:discover','--ansi']],
 ['installation-lock-status','Permanent installation lock integrity status',[PHP_BINARY,'artisan','nexora:install:lock-status','--assert-valid']],
 ['installation-lock-integrity-test','Sealed/legacy/tampered installation lock regression tests',[PHP_BINARY,'artisan','test','tests/Unit/InstallationStateIntegrityTest.php']],
 ['installation-bootstrap-receipt-test','Bootstrap dependency receipt publication/discard integrity',[PHP_BINARY,'artisan','test','tests/Unit/FreshInstallDependencyReceiptCommitTest.php']],
 ['installation-lock-http-failclosed-test','Corrupt installation lock HTTP fail-closed regression',[PHP_BINARY,'artisan','test','tests/Feature/Certification/InstallationLockIntegrityCertificationTest.php']],
 ['installer-consent-flow-test','Installer DB resume/reset, final CTA and dependency-preflight architecture',[PHP_BINARY,'artisan','test','tests/Architecture/N100V49InstallerConsentFlowArchitectureTest.php']],
 ['installation-resume-provenance-test','Interrupted-install exact provenance and incompatible-resume rejection',[PHP_BINARY,'artisan','test','tests/Unit/InstallationRecoveryTest.php']],
 ['password-risk-consent-test','Weak/Low/Medium password consent and hard-floor policy',[PHP_BINARY,'artisan','test','tests/Unit/Security/PasswordStrengthEvaluatorTest.php']],
 ['optimize-clear-before','Clear framework caches before runtime certification',[PHP_BINARY,'artisan','optimize:clear']],
 ['artisan-about','Laravel application boot',[PHP_BINARY,'artisan','about']],
 ['route-list','Route registry boot',[PHP_BINARY,'artisan','route:list']],
 ['schedule-list','Scheduler registry boot',[PHP_BINARY,'artisan','schedule:list']],
 ['database-prepare','Prepare isolated certification database',[PHP_BINARY,'scripts/create-certification-database.php']],
 ['database-version-doctor','Primary database server minimum-version doctor',[PHP_BINARY,'artisan','nexora:database:doctor']],
 ['migrate-fresh-seed','Fresh migrate + seed on isolated certification DB',[PHP_BINARY,'artisan','migrate:fresh','--seed','--force']],
 ['seed-idempotency','Repeat seed without duplicate/drift failure',[PHP_BINARY,'artisan','db:seed','--force']],
 ['tenant-seed-isolation-test','Tenant context reset + scoped default seed FK regression tests',[PHP_BINARY,'artisan','test','tests/Feature/Enterprise/EnterpriseFlowTest.php']],
 ['tenant-execution-boundary-test','Queue/scheduler tenant execution scope, missing/deleted/suspended tenant isolation',[PHP_BINARY,'artisan','test','tests/Feature/Enterprise/EnterpriseFlowTest.php','--filter=tenant_execution_scope']],
 ['database-data-plane-baseline','Seal database server/session + structural schema baseline',[PHP_BINARY,'scripts/database-data-plane-certify.php','--write=c2-core-baseline.json']],
 ['migration-reset','Full migration rollback/down round-trip',[PHP_BINARY,'artisan','migrate:reset','--force']],
 ['migration-rebuild','Rebuild all migrations after reset',[PHP_BINARY,'artisan','migrate','--force']],
 ['seed-rebuild','Seed rebuilt database',[PHP_BINARY,'artisan','db:seed','--force']],
 ['database-data-plane-rebuild','Prove reset/rebuild data-plane and schema equality',[PHP_BINARY,'scripts/database-data-plane-certify.php','--compare=c2-core-baseline.json']],
 ['runtime-sync','Runtime registry synchronization',[PHP_BINARY,'artisan','nexora:runtime:sync']],
 ['runtime-cache','Runtime registry cache compilation',[PHP_BINARY,'artisan','nexora:runtime:cache']],
 ['database-isolation-test','PHPUnit certification DB identity guard',[PHP_BINARY,'artisan','test','tests/Feature/Certification/CertificationDatabaseIsolationTest.php']],
 ['environment-doctor','Environment/config safety doctor',[PHP_BINARY,'artisan','nexora:environment:doctor']],
 ['filesystem-doctor','Filesystem safety doctor',[PHP_BINARY,'artisan','nexora:filesystem:doctor']],
 ['transfer-doctor','Transfer safety doctor',[PHP_BINARY,'artisan','nexora:transfer:doctor']],
 ['runtime-doctor','Runtime limits/queue/proxy doctor',[PHP_BINARY,'artisan','nexora:runtime:doctor']],
 ['runtime-engine-status','PHP runtime engine / extension / PDO driver identity',[PHP_BINARY,'artisan','nexora:runtime:engine-status','--deep']],
 ['database-data-plane-status','Database server/session identity + structural schema status',[PHP_BINARY,'artisan','nexora:database:data-plane-status','--deep']],
 ['runtime-storage-status','Persistent media/object/backup storage data-plane + deep round-trip status',[PHP_BINARY,'artisan','nexora:runtime:storage-status','--deep']],
 ['runtime-service-status','Cache/session/queue/mail/TLS/proxy service data-plane + deep probes',[PHP_BINARY,'artisan','nexora:runtime:service-status','--deep']],
 ['runtime-host-status','Host/platform/timezone/locale/database-clock + filesystem capability probes',[PHP_BINARY,'artisan','nexora:runtime:host-status','--deep']],
 ['runtime-resource-status','Runtime resource/capacity envelope deep probes',[PHP_BINARY,'artisan','nexora:runtime:resource-status','--deep']],
 ['runtime-policy-status','Effective secret-free policy-plane convergence + fail-closed invariants',[PHP_BINARY,'artisan','nexora:runtime:policy-status','--deep']],
 ['runtime-process-status','Runtime web/queue/scheduler process-role policy lineage',[PHP_BINARY,'artisan','nexora:runtime:process-status','--assert-installed']],
 ['runtime-dependency-status','Reviewed dependency locks + installed Laravel framework state',[PHP_BINARY,'artisan','nexora:runtime:dependency-status']],
 ['runtime-compatibility-status','Deployment/framework/dependency compatibility diagnosis',[PHP_BINARY,'artisan','nexora:runtime:compatibility-status','--deep']],
 ['concurrency-doctor','Concurrency/idempotency doctor',[PHP_BINARY,'artisan','nexora:concurrency:doctor']],
 ['phpunit-full','Laravel/PHPUnit full suite',[PHP_BINARY,'artisan','test']],
 ['pint','Laravel Pint source-format verification',[PHP_BINARY,'vendor/bin/pint','--test']],
 ['artisan-optimize','Compile production Laravel caches',[PHP_BINARY,'artisan','optimize']],
 ['optimized-about','Optimized Laravel boot',[PHP_BINARY,'artisan','about']],
 ['optimized-route-list','Optimized route registry boot',[PHP_BINARY,'artisan','route:list']],
 ['optimized-schedule-list','Optimized scheduler registry boot',[PHP_BINARY,'artisan','schedule:list']],
 ['optimized-environment-doctor','Environment doctor under optimized boot',[PHP_BINARY,'artisan','nexora:environment:doctor']],
 ['optimized-database-doctor','Database version doctor under optimized boot',[PHP_BINARY,'artisan','nexora:database:doctor']],
 ['optimize-clear-final','Clear generated framework caches after C2',[PHP_BINARY,'artisan','optimize:clear']],
];
foreach($ordered as [$id,$label,$cmd]){if($status!=='pass')break;if(!$run($id,$label,$cmd))break;}
$hash=static fn(string $file):?string=>is_file($file)?(hash_file('sha256',$file)?:null):null;
$summary=['schema'=>1,'chunk'=>'N1.0-C2','scope'=>'Laravel Runtime + Core Database Certification','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],'run_id'=>$runId,'status'=>$status,'first_blocker'=>$first,'database'=>['connection'=>$dbConnection,'database'=>basename(str_replace('\\','/',$dbDatabase))],'artifacts'=>['c1_evidence_sha256'=>$hash($root.'/storage/app/nexora/n1-c1/latest.json'),'composer_lock_sha256'=>$hash($root.'/composer.lock'),'package_lock_sha256'=>$hash($root.'/package-lock.json'),'reviewed_locks_sha256'=>$hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),'certified_toolchain_sha256'=>$hash($root.'/storage/app/nexora/certification/toolchain.json')],'steps'=>$steps,'finished_at'=>gmdate(DATE_ATOM)];
$json=json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;file_put_contents($dir.'/summary.json',$json);file_put_contents($base.'/latest.json',$json);
$md="# Nexora N1.0-C2 Laravel Runtime + Core Database Certification\n\nStatus: **".strtoupper($status)."**  \nPlatform: `{$version}`  \nSource: `{$source['tree_sha256']}`  \nDatabase: `{$dbConnection}` / `".basename(str_replace('\\','/',$dbDatabase))."`\n";if($first)$md.="First blocker: `{$first['id']}` — {$first['label']}\n";$md.="\n| Gate | Status | Exit |\n|---|---:|---:|\n";foreach($steps as $s)$md.='| '.$s['label'].' | '.strtoupper($s['status']).' | '.$s['exit_code']." |\n";file_put_contents($dir.'/summary.md',$md);file_put_contents($base.'/latest.md',$md);
fwrite(STDOUT,"\n{$md}\nEvidence: storage/app/nexora/n1-c2/{$runId}/\n");exit($status==='pass'?0:1);
