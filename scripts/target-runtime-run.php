<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';

$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$install=in_array('--install-deps',$argv,true);
$full=in_array('--full',$argv,true);
$keepGoing=in_array('--keep-going',$argv,true);
$noBundle=in_array('--no-bundle',$argv,true);
$resumeLatest=in_array('--resume-latest',$argv,true);
$resumeRunId=null;
foreach($argv as $arg) if(str_starts_with($arg,'--resume=')) $resumeRunId=trim(substr($arg,9));
if($resumeLatest && $resumeRunId!==null){ fwrite(STDERR,"[Nexora Target Runtime] Use either --resume-latest or --resume=<run-id>, not both.\n"); exit(2); }

$started=microtime(true);
$runId=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$base=$root.'/storage/app/nexora/target-runtime';
$runDir=$base.'/'.$runId;
$logDir=$runDir.'/steps';
$resumeSummary=null;
$resumeFrom=null;
if($resumeLatest){
    $latestPath=$base.'/latest.json';
    if(!is_file($latestPath)){ fwrite(STDERR,"[Nexora Target Runtime] Cannot resume: latest.json does not exist.\n"); exit(2); }
    $latest=json_decode((string)file_get_contents($latestPath),true);
    $resumeRunId=is_array($latest)?(string)($latest['run_id']??''):'';
}
if($resumeRunId!==null){
    if(preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{6}$/',$resumeRunId)!==1){ fwrite(STDERR,"[Nexora Target Runtime] Invalid resume run id.\n"); exit(2); }
    $resumePath=$base.'/'.$resumeRunId.'/summary.json';
    if(!is_file($resumePath)){ fwrite(STDERR,"[Nexora Target Runtime] Cannot resume: summary not found for {$resumeRunId}.\n"); exit(2); }
    $resumeSummary=json_decode((string)file_get_contents($resumePath),true);
    if(!is_array($resumeSummary)){ fwrite(STDERR,"[Nexora Target Runtime] Cannot resume: prior summary is invalid.\n"); exit(2); }
    $resumeFrom=$resumeRunId;
}
foreach([$base,$runDir,$logDir] as $dir){
    if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
        fwrite(STDERR,"[Nexora Target Runtime] Unable to create {$dir}.\n");
        exit(1);
    }
}

$env=NexoraBootstrapProcessEnvironment::build($root,$_ENV);
$redact=static function(string $text):string{
    foreach([
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
        '/([?&](?:token|secret|key|password)=)[^&\s]+/i',
    ] as $pattern) $text=(string)preg_replace($pattern,'$1[REDACTED]',$text);
    return $text;
};

$commandExists=static function(string $command) use($root,$env):bool{
    $cmd=PHP_OS_FAMILY==='Windows' ? 'where '.escapeshellarg($command) : 'command -v '.escapeshellarg($command);
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($proc)) return false;
    fclose($pipes[0]);
    stream_get_contents($pipes[1]); fclose($pipes[1]);
    stream_get_contents($pipes[2]); fclose($pipes[2]);
    return proc_close($proc)===0;
};

$steps=[];
$blocked=false;
$firstBlocker=null;
$record=static function(array $step) use (&$steps,&$blocked,&$firstBlocker,$logDir):void{
    $steps[]=$step;
    if(($step['status']??null)==='fail' && ($step['required']??false)){
        $blocked=true;
        if($firstBlocker===null) $firstBlocker=[
            'id'=>$step['id'],'label'=>$step['label'],'exit_code'=>$step['exit_code']??null,
            'stderr_tail'=>$step['stderr_tail']??'',
        ];
    }
    file_put_contents($logDir.'/'.$step['id'].'.stdout.log',(string)($step['stdout']??''));
    file_put_contents($logDir.'/'.$step['id'].'.stderr.log',(string)($step['stderr']??''));
};

$resumePassed=[];
$resumeSkippable=['composer-install'=>true,'npm-ci'=>true,'frontend-typecheck'=>true,'frontend-tests'=>true,'frontend-build'=>true,'frontend-build-budget'=>true];
$run=static function(string $id,string $label,array $parts,string $group,bool $required=true) use($root,$env,$redact,$record,&$blocked,$keepGoing,&$resumePassed,$resumeSkippable,$resumeFrom):bool{
    if($blocked && !$keepGoing) return false;
    if($resumeFrom!==null && isset($resumePassed[$id],$resumeSkippable[$id])){
        $message="Resumed from {$resumeFrom}; exact source/lock/dependency fingerprints match prior PASS.";
        $record(['id'=>$id,'group'=>$group,'label'=>$label,'required'=>$required,'status'=>'pass','exit_code'=>0,'duration_seconds'=>0.0,'command'=>array_values(array_map('strval',$parts)),'resumed'=>true,'stdout'=>$message."\n",'stderr'=>'','stdout_tail'=>$message,'stderr_tail'=>'']);
        fwrite(STDOUT,"\n[Nexora Target Runtime] {$label}\n[RESUME] {$message}\n");
        return true;
    }
    $cmd=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$parts));
    fwrite(STDOUT,"\n[Nexora Target Runtime] {$label}\n> {$cmd}\n");
    $started=microtime(true);
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($proc)){
        $stdout='';$stderr='Unable to start process.';$exit=127;
    }else{
        fclose($pipes[0]);
        $stdout=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);
        $stderr=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);
        $exit=proc_close($proc);
    }
    $stdout=$redact($stdout);$stderr=$redact($stderr);
    $record([
        'id'=>$id,'group'=>$group,'label'=>$label,'required'=>$required,
        'status'=>$exit===0?'pass':'fail','exit_code'=>$exit,
        'duration_seconds'=>round(microtime(true)-$started,3),'command'=>array_values(array_map('strval',$parts)),
        'stdout'=>$stdout,'stderr'=>$stderr,
        'stdout_tail'=>substr($stdout,max(0,strlen($stdout)-4000)),
        'stderr_tail'=>substr($stderr,max(0,strlen($stderr)-4000)),
    ]);
    if($stdout!=='') fwrite(STDOUT,$stdout.(str_ends_with($stdout,"\n")?'':"\n"));
    if($stderr!=='') fwrite(STDERR,$stderr.(str_ends_with($stderr,"\n")?'':"\n"));
    return $exit===0;
};

$fail=static function(string $id,string $label,string $message,string $group='prerequisites') use($record):void{
    $record([
        'id'=>$id,'group'=>$group,'label'=>$label,'required'=>true,'status'=>'fail','exit_code'=>127,
        'duration_seconds'=>0.0,'command'=>null,'stdout'=>'','stderr'=>$message."\n",
        'stdout_tail'=>'','stderr_tail'=>$message,
    ]);
    fwrite(STDERR,"[Nexora Target Runtime] {$label}: {$message}\n");
};

$sourceAttestation=nexoraComputeSourceAttestation($root);
$environment=[
    'platform_version'=>$version,
    'source_tree_sha256'=>$sourceAttestation['tree_sha256'],
    'source_file_count'=>$sourceAttestation['file_count'],
    'php_version'=>PHP_VERSION,
    'os_family'=>PHP_OS_FAMILY,
    'composer_available'=>$commandExists('composer'),
    'node_available'=>$commandExists('node'),
    'npm_available'=>$commandExists('npm'),
    'composer_lock_sha256'=>is_file($root.'/composer.lock')?(hash_file('sha256',$root.'/composer.lock')?:null):null,
    'package_lock_sha256'=>is_file($root.'/package-lock.json')?(hash_file('sha256',$root.'/package-lock.json')?:null):null,
    'vendor_installed_sha256'=>is_file($root.'/vendor/composer/installed.json')?(hash_file('sha256',$root.'/vendor/composer/installed.json')?:null):null,
    'node_modules_lock_sha256'=>is_file($root.'/node_modules/.package-lock.json')?(hash_file('sha256',$root.'/node_modules/.package-lock.json')?:null):null,
    'vendor_autoload'=>is_file($root.'/vendor/autoload.php'),
    'node_modules'=>is_dir($root.'/node_modules'),
    'full_certification_requested'=>$full,
    'install_dependencies_requested'=>$install,
    'resume_from'=>$resumeFrom,
];
if(is_array($resumeSummary)){
    $priorEnv=(array)($resumeSummary['environment']??[]);
    foreach(['platform_version','source_tree_sha256','composer_lock_sha256','package_lock_sha256','vendor_installed_sha256','node_modules_lock_sha256'] as $fingerprint){
        if(($priorEnv[$fingerprint]??null)!==($environment[$fingerprint]??null)){
            fwrite(STDERR,"[Nexora Target Runtime] Cannot resume: fingerprint drift in {$fingerprint}. Start a new run.\n"); exit(2);
        }
    }
    foreach((array)($resumeSummary['steps']??[]) as $priorStep) if(($priorStep['status']??null)==='pass') $resumePassed[(string)($priorStep['id']??'')]=true;
}
file_put_contents($runDir.'/environment.json',json_encode($environment,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));

// Phase 0: dependency-free guards catch source regressions before toolchain work.
$run('source-preflight','Source preflight',[PHP_BINARY,'scripts/certification-preflight.php','--source-only'],'source');
$run('target-bootstrap','Target environment bootstrap/readiness',[PHP_BINARY,'scripts/target-environment-bootstrap.php','--json'],'source');
$run('target-runtime-contract','Target runtime runner contract',[PHP_BINARY,'scripts/target-runtime-contract-verify.php'],'source');
$run('inertia-contract','Inertia frontend contract',[PHP_BINARY,'scripts/inertia-frontend-contract-verify.php'],'source');

// Phase 1: fail closed on missing executables and reviewed lockfiles.
if((!$blocked || $keepGoing) && !$environment['composer_available']) $fail('composer-required','Composer prerequisite','Composer executable not found in PATH.');
if((!$blocked || $keepGoing) && !$environment['node_available']) $fail('node-required','Node prerequisite','Node executable not found in PATH.');
if((!$blocked || $keepGoing) && !$environment['npm_available']) $fail('npm-required','npm prerequisite','npm executable not found in PATH.');
if((!$blocked || $keepGoing) && !$environment['composer_lock_sha256']) $fail('composer-lock-required','Reviewed composer.lock','composer.lock is required. Target certification never resolves an unlocked Composer graph.','dependencies');
if((!$blocked || $keepGoing) && !$environment['package_lock_sha256']) $fail('package-lock-required','Reviewed package-lock.json','package-lock.json is required. Target certification never resolves an unlocked npm graph.','dependencies');

if(!$blocked || $keepGoing){
    $run('dependency-lock-review','Reviewed dependency lock attestation',[PHP_BINARY,'scripts/dependency-lock-review.php','--verify-attestation'],'dependencies');
    $run('dependency-contract-strict','Strict lockfile/source dependency policy',[PHP_BINARY,'scripts/dependency-contract-verify.php','--strict-locks'],'dependencies');
    $run('dependency-runtime','Dependency runtime compatibility',[PHP_BINARY,'scripts/dependency-runtime-verify.php'],'dependencies');
}

// Phase 2: install only from reviewed locks when explicitly requested.
if($install && (!$blocked || $keepGoing)){
    $run('composer-install','Composer install from reviewed lock',['composer','install','--no-interaction','--prefer-dist','--optimize-autoloader','--no-progress'],'dependencies');
    $run('npm-ci','npm ci from reviewed lock',['npm','ci','--no-audit','--no-fund'],'dependencies');
}

// Dependency directories must exist whether installed by this run or prepared beforehand.
if((!$blocked || $keepGoing) && !is_file($root.'/vendor/autoload.php')) $fail('vendor-required','Composer dependencies','vendor/autoload.php is missing. Run with --install-deps or install the reviewed Composer lock.','dependencies');
if((!$blocked || $keepGoing) && !is_dir($root.'/node_modules')) $fail('node-modules-required','Node dependencies','node_modules is missing. Run with --install-deps or npm ci from the reviewed lock.','dependencies');

// Phase 3: authoritative frontend target evidence. Source contracts do not substitute for these commands.
if(!$blocked || $keepGoing){
    $run('frontend-typecheck','TypeScript typecheck',['npm','run','typecheck'],'frontend');
    $run('frontend-tests','Vitest suite',['npm','run','test'],'frontend');
    $run('frontend-build','Vite production build',['npm','run','build'],'frontend');
    $run('frontend-build-budget','Production asset/budget verification',[PHP_BINARY,'scripts/performance-build-verify.php'],'frontend');
}

// Phase 4: Laravel integration boot. No destructive migration is performed against the ambient project DB here.
if(!$blocked || $keepGoing){
    $run('package-discover','Laravel package discovery',[PHP_BINARY,'artisan','package:discover','--ansi'],'laravel');
    $run('optimize-clear','Clear Laravel runtime caches',[PHP_BINARY,'artisan','optimize:clear'],'laravel');
    $run('artisan-about','Laravel application boot',[PHP_BINARY,'artisan','about'],'laravel');
    $run('route-list','Laravel route registry',[PHP_BINARY,'artisan','route:list'],'laravel');
    $run('schedule-list','Laravel scheduler registry',[PHP_BINARY,'artisan','schedule:list'],'laravel');
    foreach([
        ['database-doctor','Database doctor','nexora:database:doctor'],
        ['environment-doctor','Environment doctor','nexora:environment:doctor'],
        ['filesystem-doctor','Filesystem doctor','nexora:filesystem:doctor'],
        ['transfer-doctor','Transfer doctor','nexora:transfer:doctor'],
        ['runtime-doctor','Runtime doctor','nexora:runtime:doctor'],
        ['concurrency-doctor','Concurrency doctor','nexora:concurrency:doctor'],
    ] as [$id,$label,$command]) $run($id,$label,[PHP_BINARY,'artisan',$command],'doctors');
}

// Phase 5: destructive work is opt-in and delegated to the existing isolated DB certification engine.
if($full && (!$blocked || $keepGoing)){
    $run('isolated-full-certification','Isolated migrations/seeds/PHPUnit/frontend certification',[PHP_BINARY,'scripts/certify-release.php','--no-package'],'certification');
}

// Closure status is informative here; missing operator evidence is expected until later gates are performed.
if(!$blocked || $keepGoing) $run('closure-status','N1.0 closure ledger',[PHP_BINARY,'scripts/final-closure-status.php'],'closure',false);

$status=$blocked?'blocked':($full?'target-certification-pass':'target-readiness-pass');
$summary=[
    'schema'=>2,'status'=>$status,'platform_version'=>$version,'run_id'=>$runId,'resume_from'=>$resumeFrom,
    'started_at'=>gmdate(DATE_ATOM,(int)$started),'finished_at'=>gmdate(DATE_ATOM),
    'duration_seconds'=>round(microtime(true)-$started,3),
    'first_blocker'=>$firstBlocker,
    'environment'=>$environment,
    'steps'=>array_map(static function(array $step):array{
        unset($step['stdout'],$step['stderr']);
        $step['stdout_log']='steps/'.$step['id'].'.stdout.log';
        $step['stderr_log']='steps/'.$step['id'].'.stderr.log';
        return $step;
    },$steps),
];
file_put_contents($runDir.'/summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
$md="# Nexora {$version} target runtime\n\n";
$md.="- Status: **{$status}**\n- Run: `{$runId}`\n- First blocker: **".($firstBlocker['label']??'none')."**\n- Full isolated certification: **".($full?'requested':'not requested')."**\n\n";
$md.="| Phase | Step | Status | Exit |\n|---|---|---:|---:|\n";
foreach($summary['steps'] as $step) $md.='| '.$step['group'].' | '.str_replace('|','\\|',$step['label']).' | '.strtoupper($step['status']).' | '.($step['exit_code']??'—')." |\n";
$md.="\nThis report does not contain `.env` or an environment dump. Token/password/cookie-shaped command output is redacted. `--full` delegates destructive database work to Nexora's isolated certification database safeguards.\n";
file_put_contents($runDir.'/summary.md',$md);
file_put_contents($base.'/latest.json',json_encode(['run_id'=>$runId,'platform_version'=>$version,'status'=>$status,'first_blocker'=>$firstBlocker,'path'=>$runDir,'updated_at'=>gmdate(DATE_ATOM)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);

$bundle=null;
if(!$noBundle && class_exists(ZipArchive::class)){
    $bundle=$base.'/Nexora_Target_Runtime_'.$version.'_'.$runId.'.zip';
    $zip=new ZipArchive();
    if($zip->open($bundle,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true){
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runDir,FilesystemIterator::SKIP_DOTS));
        foreach($iterator as $file){
            if(!$file->isFile()) continue;
            $relative=str_replace('\\','/',substr($file->getPathname(),strlen($runDir)+1));
            $zip->addFile($file->getPathname(),$relative);
        }
        $zip->close();
    } else $bundle=null;
}

fwrite(STDOUT,"\n[Nexora Target Runtime] ".strtoupper($status)." — {$version}\n");
fwrite(STDOUT,"Report: {$runDir}/summary.md\n");
if($bundle!==null) fwrite(STDOUT,"Bundle: {$bundle}\n");
elseif(!$noBundle) fwrite(STDOUT,"Bundle: not created (PHP ext-zip unavailable). Send the run directory instead.\n");
if($firstBlocker!==null) fwrite(STDERR,"First blocker: {$firstBlocker['label']}\n");
exit($blocked?1:0);
