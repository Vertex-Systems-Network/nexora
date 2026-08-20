<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';
$platform=require $root.'/config/nexora.php';$version=(string)($platform['version']??'unknown');
$statusOnly=in_array('--status-only',$argv,true);
$base=$root.'/storage/app/nexora/n1-c3';
if($statusOnly){$p=$base.'/latest.json';if(is_file($p)){fwrite(STDOUT,(string)file_get_contents($p));exit(0);}fwrite(STDOUT,"[N1.0-C3] No prior C3 evidence.\n");exit(2);}
$runId=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));$dir=$base.'/'.$runId;$logs=$dir.'/steps';foreach([$base,$dir,$logs] as $d)if(!is_dir($d)&&!mkdir($d,0775,true)&&!is_dir($d))throw new RuntimeException("Unable to create {$d}");
$source=nexoraComputeSourceAttestation($root);$env=NexoraBootstrapProcessEnvironment::build($root,['APP_ENV'=>'testing','APP_DEBUG'=>'false','NEXORA_INSTALLER_BYPASS'=>'true','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','QUEUE_CONNECTION'=>'sync']);
$steps=[];$status='pass';$first=null;
$redact=static function(string $text):string{foreach(['/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i','/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i'] as $p)$text=(string)preg_replace($p,'$1[REDACTED]',$text);return $text;};
$run=static function(string $id,string $label,array $cmd)use($root,$env,$logs,$redact,&$steps,&$status,&$first):bool{
    $line=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$cmd));fwrite(STDOUT,"\n[N1.0-C3] {$label}\n> {$line}\n");$started=microtime(true);
    $p=@proc_open($line,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($p)){$out='';$err='Unable to start process.';$code=127;}else{fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($p);}
    $out=$redact($out);$err=$redact($err);file_put_contents("{$logs}/{$id}.stdout.log",$out);file_put_contents("{$logs}/{$id}.stderr.log",$err);if($out!=='')fwrite(STDOUT,$out.(str_ends_with($out,"\n")?'':"\n"));if($err!=='')fwrite(STDERR,$err.(str_ends_with($err,"\n")?'':"\n"));
    $ok=$code===0;$steps[]=['id'=>$id,'label'=>$label,'status'=>$ok?'pass':'fail','required'=>true,'exit_code'=>$code,'duration_seconds'=>round(microtime(true)-$started,3),'stdout_log'=>"steps/{$id}.stdout.log",'stderr_log'=>"steps/{$id}.stderr.log"];if(!$ok){$status='blocked';if($first===null)$first=['id'=>$id,'label'=>$label,'exit_code'=>$code];}return $ok;
};
$ordered=[
 ['c2-evidence','Exact-source C2 PASS evidence',[PHP_BINARY,'scripts/n1-c2-evidence-verify.php']],
 ['installed-dependencies','Installed dependency graph still matches C1 reviewed locks',[PHP_BINARY,'scripts/n1-c1-installed-dependency-verify.php']],
 ['matrix-prerequisites','Five-driver PDO prerequisite gate',[PHP_BINARY,'scripts/n1-c3-matrix-prerequisite.php']],
 ['strict-five-db-matrix','Strict MySQL/MariaDB/PostgreSQL/SQLite/SQL Server matrix',[PHP_BINARY,'scripts/certify-database-matrix.php','--drivers=mysql,mariadb,pgsql,sqlite,sqlsrv','--strict']],
 ['matrix-evidence','Validate exact-source/locks/C2 matrix evidence',[PHP_BINARY,'scripts/n1-c3-database-matrix-evidence-verify.php']],
];
foreach($ordered as [$id,$label,$cmd]){if($status!=='pass')break;if(!$run($id,$label,$cmd))break;}
$hash=static fn(string $f):?string=>is_file($f)?(hash_file('sha256',$f)?:null):null;
$matrixPath=$root.'/storage/app/nexora/certification/database-matrix.json';
$summary=['schema'=>1,'chunk'=>'N1.0-C3','scope'=>'Strict Five-Database Portability Matrix','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],'run_id'=>$runId,'status'=>$status,'first_blocker'=>$first,'required_drivers'=>['mysql','mariadb','pgsql','sqlite','sqlsrv'],'artifacts'=>['c2_evidence_sha256'=>$hash($root.'/storage/app/nexora/n1-c2/latest.json'),'composer_lock_sha256'=>$hash($root.'/composer.lock'),'package_lock_sha256'=>$hash($root.'/package-lock.json'),'reviewed_locks_sha256'=>$hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),'certified_toolchain_sha256'=>$hash($root.'/storage/app/nexora/certification/toolchain.json'),'database_matrix_sha256'=>$hash($matrixPath)],'steps'=>$steps,'finished_at'=>gmdate(DATE_ATOM)];
$json=json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;file_put_contents($dir.'/summary.json',$json);file_put_contents($base.'/latest.json',$json);
$md="# Nexora N1.0-C3 Strict Five-Database Matrix\n\nStatus: **".strtoupper($status)."**  \nPlatform: `{$version}`  \nSource: `{$source['tree_sha256']}`\n";if($first)$md.="First blocker: `{$first['id']}` — {$first['label']}\n";$md.="\n| Gate | Status | Exit |\n|---|---:|---:|\n";foreach($steps as $s)$md.='| '.$s['label'].' | '.strtoupper($s['status']).' | '.$s['exit_code']." |\n";file_put_contents($dir.'/summary.md',$md);file_put_contents($base.'/latest.md',$md);fwrite(STDOUT,"\n{$md}\nEvidence: storage/app/nexora/n1-c3/{$runId}/\n");exit($status==='pass'?0:1);
