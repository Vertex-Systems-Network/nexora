<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/target-support-capsule.php';
require_once $root.'/scripts/lib/n1-target-plan.php';
require_once $root.'/scripts/lib/n1-target-progress.php';
require_once $root.'/scripts/lib/n1-target-run-lock.php';
$version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
$install=in_array('--install-deps',$argv,true);
$applyExtensions=in_array('--apply-extensions',$argv,true);
$refreshLocks=in_array('--refresh-locks',$argv,true);
$prepareKits=in_array('--prepare-kits',$argv,true);
$reviewLocks=in_array('--review-locks',$argv,true);
$resumeLatest=in_array('--resume-latest',$argv,true);
$verifyRestart=in_array('--verify-restart',$argv,true);
$statusOnly=in_array('--status-only',$argv,true);
$planOnly=in_array('--plan',$argv,true);
$operator='';$reviewer='';$baseUrl='';$c4Evidence='';$c5Evidence='';$c6Evidence='';$confirmRefresh='';$confirmReview='';
foreach($argv as $arg){
    if(str_starts_with($arg,'--operator='))$operator=trim(substr($arg,11));
    elseif(str_starts_with($arg,'--reviewer='))$reviewer=trim(substr($arg,11));
    elseif(str_starts_with($arg,'--base-url='))$baseUrl=trim(substr($arg,11));
    elseif(str_starts_with($arg,'--c4-evidence='))$c4Evidence=trim(substr($arg,14));
    elseif(str_starts_with($arg,'--c5-evidence='))$c5Evidence=trim(substr($arg,14));
    elseif(str_starts_with($arg,'--c6-evidence='))$c6Evidence=trim(substr($arg,14));
    elseif(str_starts_with($arg,'--confirm-refresh='))$confirmRefresh=trim(substr($arg,18));
    elseif(str_starts_with($arg,'--confirm-review='))$confirmReview=trim(substr($arg,17));
}
if($applyExtensions&&($install||$refreshLocks||$reviewLocks||$verifyRestart)){fwrite(STDERR,"[N1.0 Target Execution] Run --apply-extensions alone, restart Laragon, then use --refresh-locks or --install-deps in a fresh terminal.\n");exit(2);}
if($refreshLocks&&($install||$reviewLocks)){fwrite(STDERR,"[N1.0 Target Execution] --refresh-locks cannot be combined with --install-deps or --review-locks; refreshed locks require a separate human review step before installation.\n");exit(2);}
if($reviewLocks&&($reviewer===''||$confirmReview!=='REVIEWED')){fwrite(STDERR,"[N1.0 Target Execution] --review-locks requires --reviewer=<name> and explicit --confirm-review=REVIEWED.\n");exit(2);}
if($refreshLocks&&$confirmRefresh!=='REFRESH'){fwrite(STDERR,"[N1.0 Target Execution] --refresh-locks requires explicit --confirm-refresh=REFRESH.\n");exit(2);}
$provided=array_filter([$c4Evidence,$c5Evidence,$c6Evidence],static fn($v)=>$v!=='');
if($refreshLocks&&$provided!==[]){fwrite(STDERR,"[N1.0 Target Execution] Lock refresh cannot be combined with C4/C5/C6 operator evidence execution.\n");exit(2);}
if($provided!==[]&&count($provided)!==3){fwrite(STDERR,"[N1.0 Target Execution] C4, C5 and C6 evidence paths must be supplied together.\n");exit(2);}
if($provided!==[]&&($baseUrl===''||$operator==='')){fwrite(STDERR,"[N1.0 Target Execution] --base-url and --operator are required when executing operator-evidence phases.\n");exit(2);}
$base=$root.'/storage/app/nexora/n1-target-execution';
if($planOnly){$plan=nexoraBuildN10TargetPlan($root);fwrite(STDOUT,json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);exit(0);}
if($prepareKits&&($operator===''||$operator==='operator-name')){fwrite(STDERR,"[N1.0 Target Execution] --prepare-kits requires a real --operator=\"NAME\".\n");exit(2);}
if($statusOnly){$latest=$base.'/latest.json';if(is_file($latest)){fwrite(STDOUT,(string)file_get_contents($latest));exit(0);}fwrite(STDOUT,"[N1.0 Target Execution] No prior execution evidence.\n");exit(2);}
$promotionJournalPath=$root.'/storage/app/nexora/dependency-intake/lock-promotion-journal.json';if(is_file($promotionJournalPath)){try{$promotionJournal=json_decode((string)file_get_contents($promotionJournalPath),true,512,JSON_THROW_ON_ERROR);}catch(Throwable){$promotionJournal=['status'=>'invalid'];}if(!in_array((string)($promotionJournal['status']??''),['complete','rolled-back'],true)){fwrite(STDERR,"[N1.0 Target Execution] Incomplete dependency lock promotion detected. Run `scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK` before target execution.\n");exit(2);}}
$runId=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));try{$executionLock=nexoraAcquireTargetExecutionLock($root,$runId);}catch(Throwable $e){fwrite(STDERR,'[N1.0 Target Execution] '.$e->getMessage().PHP_EOL);exit(2);}register_shutdown_function(static function()use(&$executionLock):void{nexoraReleaseTargetExecutionLock($executionLock);});$dir=$base.'/'.$runId;$logs=$dir.'/steps';$kits=$dir.'/operator-kits';
foreach([$base,$dir,$logs] as $d)if(!is_dir($d)&&!mkdir($d,0775,true)&&!is_dir($d))throw new RuntimeException("Unable to create {$d}");
$source=nexoraComputeSourceAttestation($root);$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);$steps=[];$status='pass';$first=null;
$redact=static function(string $text):string{foreach(['/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i','/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i'] as $p)$text=(string)preg_replace($p,'$1[REDACTED]',$text);return $text;};
$run=static function(string $id,string $label,array $cmd)use($root,$env,$logs,$redact,&$steps,&$status,&$first):bool{$line=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$cmd));fwrite(STDOUT,"\n[N1.0 Target Execution] {$label}\n> {$line}\n");$started=microtime(true);$p=@proc_open($line,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);if(!is_resource($p)){$out='';$err='Unable to start process.';$code=127;}else{fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($p);}$out=$redact($out);$err=$redact($err);file_put_contents("{$logs}/{$id}.stdout.log",$out);file_put_contents("{$logs}/{$id}.stderr.log",$err);if($out!=='')fwrite(STDOUT,$out.(str_ends_with($out,"\n")?'':"\n"));if($err!=='')fwrite(STDERR,$err.(str_ends_with($err,"\n")?'':"\n"));$ok=$code===0;$steps[]=['id'=>$id,'label'=>$label,'status'=>$ok?'pass':'fail','required'=>true,'exit_code'=>$code,'duration_seconds'=>round(microtime(true)-$started,3),'stdout_log'=>"steps/{$id}.stdout.log",'stderr_log'=>"steps/{$id}.stderr.log"];if(!$ok){$status=$code===2&&$id==='c1'&&str_contains($out.$err,'restart')?'restart-required':($code===2&&$id==='lock-refresh'?'lock-review-required':'blocked');if($first===null)$first=['id'=>$id,'label'=>$label,'exit_code'=>$code];}return $ok;};
$probe=static function(string $id,string $label,array $cmd)use($root,$env,$logs,$redact,&$steps):bool{$line=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$cmd));fwrite(STDOUT,"\n[N1.0 Target Execution] {$label}\n> {$line}\n");$started=microtime(true);$p=@proc_open($line,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);if(!is_resource($p)){$out='';$err='Unable to start process.';$code=127;}else{fclose($pipes[0]);$out=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);$err=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($p);}$out=$redact($out);$err=$redact($err);file_put_contents("{$logs}/{$id}.stdout.log",$out);file_put_contents("{$logs}/{$id}.stderr.log",$err);if($out!==''&&$code===0)fwrite(STDOUT,$out.(str_ends_with($out,"\n")?'':"\n"));$ok=$code===0;$steps[]=['id'=>$id,'label'=>$label,'status'=>$ok?'reused-pass':'rerun-required','required'=>false,'exit_code'=>$code,'duration_seconds'=>round(microtime(true)-$started,3),'stdout_log'=>"steps/{$id}.stdout.log",'stderr_log'=>"steps/{$id}.stderr.log"];return $ok;};

$progressCheckpoint=static function(string $label)use($root):void{
    $progress=nexoraBuildN10GranularProgress($root);
    nexoraPersistN10GranularProgress($root,$progress);
    fwrite(STDOUT,"\n[N1.0 Target Progress] {$label}\n".nexoraRenderN10GranularProgress($progress)."\n");
    fwrite(STDOUT,"Progress file: storage/app/nexora/n1-target-execution/target-progress.json\n");
};

// Source proof first, then restart/review handoffs and resumable automated C1-C3 in strict order.
if(!$run('source-certification','Exact-source certification/preflight',[PHP_BINARY,'scripts/certify-release.php','--source-only'])){}
$restartTicket=$root.'/storage/app/nexora/target-remediation/restart-ticket.json';
if($status==='pass'&&($verifyRestart||is_file($restartTicket)))$run('restart-verification','Verify remediated Laragon PHP restart ticket',[PHP_BINARY,'scripts/target-prerequisite-restart-verify.php']);
if($status==='pass'&&$refreshLocks){
    $run('prerequisite-remediation','Target prerequisite remediation review',[PHP_BINARY,'scripts/target-prerequisite-remediate.php','--no-write']);
    if($status==='pass')$run('lock-refresh','Refresh Composer/npm lockfiles for explicit human review',[PHP_BINARY,'scripts/dependency-lock-refresh.php','--confirm='.$confirmRefresh]);
}
if($status==='pass'&&$reviewLocks){
    $run('lock-review','Promote the human-reviewed dependency lock candidate pair',[PHP_BINARY,'scripts/dependency-lock-promote.php','--reviewer='.$reviewer,'--confirm=PROMOTE-REVIEWED']);
    if($status==='pass')$run('lock-review-verify','Verify reviewed-lock attestation',[PHP_BINARY,'scripts/dependency-lock-review.php','--verify-attestation','--require-refresh-handoff']);
}
$c1Reusable=false;$c2Reusable=false;$c3Reusable=false;
if($status==='pass'&&$resumeLatest)$c1Reusable=$probe('resume-c1','Revalidate existing C1 PASS evidence',[PHP_BINARY,'scripts/n1-c1-evidence-verify.php']);
if($status==='pass'&&!$c1Reusable){$cmd=[PHP_BINARY,'scripts/n1-c1-dependency-certify.php'];if($applyExtensions)$cmd[]='--apply-extensions';elseif($install)$cmd[]='--install-deps';$run('c1','C1 target environment + dependencies',$cmd);$progressCheckpoint('After C1');}
if($status==='pass'&&$resumeLatest)$c2Reusable=$probe('resume-c2','Revalidate existing C2 PASS evidence',[PHP_BINARY,'scripts/n1-c2-evidence-verify.php']);
if($status==='pass'&&!$c2Reusable){$run('c2','C2 Laravel runtime + core DB',[PHP_BINARY,'scripts/n1-c2-laravel-runtime-certify.php']);$progressCheckpoint('After C2');}
if($status==='pass'&&$resumeLatest)$c3Reusable=$probe('resume-c3','Revalidate existing C3 PASS evidence',[PHP_BINARY,'scripts/n1-c3-database-matrix-evidence-verify.php']);
if($status==='pass'&&!$c3Reusable){$run('c3','C3 strict five-database matrix',[PHP_BINARY,'scripts/n1-c3-database-matrix-certify.php']);$progressCheckpoint('After C3');}
if($status==='pass')$run('certification-session','Ensure exact-source/lock certification session',[PHP_BINARY,'scripts/n1-certification-session.php','--ensure']);

$kitPaths=null;
if($status==='pass'&&$provided===[]){
    if($operator===''&&!$prepareKits){$status='operator-action-required';$first=['id'=>'operator-evidence','label'=>'C4/C5/C6 real operator evidence','exit_code'=>2];}
    else{
        $kitPaths=['c4'=>$kits.'/c4','c5'=>$kits.'/c5','c6'=>$kits.'/c6'];
        foreach($kitPaths as $path)if(!is_dir($path)&&!mkdir($path,0775,true)&&!is_dir($path))throw new RuntimeException("Unable to create {$path}");
        $run('prepare-c4','Prepare fail-closed C4 operator kit',[PHP_BINARY,'scripts/n1-c4-evidence-prepare.php','--operator='.$operator,'--out='.$kitPaths['c4']]);
        if($status==='pass')$run('prepare-c5','Prepare fail-closed C5 operator kit',[PHP_BINARY,'scripts/n1-c5-evidence-prepare.php','--operator='.$operator,'--out='.$kitPaths['c5']]);
        if($status==='pass')$run('prepare-c6','Prepare fail-closed C6 HA operator kit',[PHP_BINARY,'scripts/n1-c6-evidence-prepare.php','--operator='.$operator,'--out='.$kitPaths['c6']]);
        if($status==='pass'){$status='operator-action-required';$first=['id'=>'operator-evidence','label'=>'Complete generated C4/C5/C6 operator evidence kits','exit_code'=>2];}
    }
}
if($status==='pass'&&$provided!==[]){
    $run('c4','C4 install / upgrade / backup recovery',[PHP_BINARY,'scripts/n1-c4-operations-certify.php','--evidence='.$c4Evidence]);$progressCheckpoint('After C4');
    if($status==='pass'){$run('c5','C5 browser / A11y / RTL / performance',[PHP_BINARY,'scripts/n1-c5-browser-performance-certify.php','--base-url='.$baseUrl,'--evidence='.$c5Evidence]);$progressCheckpoint('After C5');}
    if($status==='pass'){$run('c6','C6 HA + final production closure',[PHP_BINARY,'scripts/n1-c6-final-certify.php','--base-url='.$baseUrl,'--evidence='.$c6Evidence]);$progressCheckpoint('After C6');}
}
$hash=static fn(string $f):?string=>is_file($f)?(hash_file('sha256',$f)?:null):null;
$summary=['schema'=>1,'phase'=>'N1.0 Target Execution Pack','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],'run_id'=>$runId,'status'=>$status,'first_blocker'=>$first,'options'=>['install_dependencies'=>$install,'apply_extensions'=>$applyExtensions,'verify_restart'=>$verifyRestart,'refresh_locks'=>$refreshLocks,'review_locks'=>$reviewLocks,'resume_latest'=>$resumeLatest,'prepare_kits'=>$prepareKits,'plan_only'=>$planOnly,'base_url'=>$baseUrl!==''?$baseUrl:null,'operator'=>$operator!==''?$operator:null],'operator_kits'=>$kitPaths,'artifacts'=>['c1_sha256'=>$hash($root.'/storage/app/nexora/n1-c1/latest.json'),'c2_sha256'=>$hash($root.'/storage/app/nexora/n1-c2/latest.json'),'c3_sha256'=>$hash($root.'/storage/app/nexora/certification/database-matrix.json'),'c4_sha256'=>$hash($root.'/storage/app/nexora/n1-c4/c4-evidence.json'),'c5_sha256'=>$hash($root.'/storage/app/nexora/n1-c5/c5-evidence.json'),'c6_sha256'=>$hash($root.'/storage/app/nexora/n1-c6/c6-evidence.json')],'steps'=>$steps,'granular_progress'=>nexoraBuildN10GranularProgress($root),'finished_at'=>gmdate(DATE_ATOM)];
$json=json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;file_put_contents($dir.'/summary.json',$json);file_put_contents($base.'/latest.json',$json);
$capsule=nexoraBuildTargetSupportCapsule($root,$dir,$summary);$capsuleJson=json_encode($capsule,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;$capsuleSha=hash('sha256',$capsuleJson);file_put_contents($dir.'/support-capsule.json',$capsuleJson);file_put_contents($dir.'/support-capsule.sha256',$capsuleSha."  support-capsule.json\n");file_put_contents($base.'/latest-support.json',$capsuleJson);file_put_contents($base.'/latest-support.sha256',$capsuleSha."  latest-support.json\n");
$md="# Nexora N1.0 Target Execution Pack\n\nStatus: **".strtoupper($status)."**  \nPlatform: `{$version}`  \nSource: `{$source['tree_sha256']}`\n";if($first)$md.="First blocker/action: `{$first['id']}` — {$first['label']}\n";$md.="\n| Phase | Status | Exit |\n|---|---:|---:|\n";foreach($steps as $s)$md.='| '.$s['label'].' | '.strtoupper($s['status']).' | '.$s['exit_code']." |\n";if($kitPaths!==null)$md.="\nOperator kits: `".str_replace('\\','/',$kits)."/`\n";file_put_contents($dir.'/summary.md',$md);file_put_contents($base.'/latest.md',$md);fwrite(STDOUT,"\n{$md}\nEvidence: storage/app/nexora/n1-target-execution/{$runId}/\nSupport capsule: storage/app/nexora/n1-target-execution/latest-support.json\nSupport SHA-256: {$capsuleSha}\n");
exit($status==='pass'?0:(in_array($status,['operator-action-required','restart-required','lock-review-required'],true)?2:1));
