<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$install=in_array('--install-deps',$argv,true);
$full=in_array('--full',$argv,true);
$noBundle=in_array('--no-bundle',$argv,true);

$started=microtime(true);
$runId=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$baseDir=$root.'/storage/app/nexora/target-diagnostics';
$runDir=$baseDir.'/'.$runId;
$logDir=$runDir.'/steps';
foreach([$baseDir,$runDir,$logDir] as $dir){
    if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
        fwrite(STDERR,"[Nexora Target Diagnostics] Unable to create {$dir}.\n");
        exit(1);
    }
}

$redact=static function(string $text):string{
    $patterns=[
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
        '/([?&](?:token|secret|key|password)=)[^&\s]+/i',
    ];
    foreach($patterns as $pattern) $text=(string)preg_replace($pattern,'$1[REDACTED]',$text);
    return $text;
};

$commandExists=static function(string $command) use($root):bool{
    $cmd=PHP_OS_FAMILY==='Windows' ? 'where '.escapeshellarg($command) : 'command -v '.escapeshellarg($command);
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,null,['bypass_shell'=>false]);
    if(!is_resource($proc)) return false;
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);fclose($pipes[1]);
    stream_get_contents($pipes[2]);fclose($pipes[2]);
    return proc_close($proc)===0;
};

$steps=[];
$run=static function(string $id,string $label,array $parts,string $group,bool $required=false) use(&$steps,$root,$logDir,$redact):int{
    $cmd=implode(' ',array_map(static fn($v)=>escapeshellarg((string)$v),$parts));
    $started=microtime(true);
    $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,null,['bypass_shell'=>false]);
    if(!is_resource($proc)){
        $stdout='';$stderr='Unable to start process.';$exit=127;
    }else{
        fclose($pipes[0]);
        $stdout=(string)stream_get_contents($pipes[1]);fclose($pipes[1]);
        $stderr=(string)stream_get_contents($pipes[2]);fclose($pipes[2]);
        $exit=proc_close($proc);
    }
    $stdout=$redact($stdout);$stderr=$redact($stderr);
    file_put_contents($logDir.'/'.$id.'.stdout.log',$stdout);
    file_put_contents($logDir.'/'.$id.'.stderr.log',$stderr);
    $steps[]=[
        'id'=>$id,'group'=>$group,'label'=>$label,'required'=>$required,'exit_code'=>$exit,
        'status'=>$exit===0?'pass':'fail','duration_seconds'=>round(microtime(true)-$started,3),
        'command'=>implode(' ',array_map(static fn($v)=>(string)$v,$parts)),
        'stdout_log'=>'steps/'.$id.'.stdout.log','stderr_log'=>'steps/'.$id.'.stderr.log',
        'stdout_tail'=>substr($stdout,max(0,strlen($stdout)-3000)),
        'stderr_tail'=>substr($stderr,max(0,strlen($stderr)-3000)),
    ];
    fwrite(STDOUT,sprintf("[Nexora Target Diagnostics] %-28s %s\n",$label,$exit===0?'PASS':'ISSUE'));
    return $exit;
};

$recordSkip=static function(string $id,string $label,string $group,string $reason) use(&$steps,$logDir):void{
    file_put_contents($logDir.'/'.$id.'.stdout.log',$reason."\n");
    file_put_contents($logDir.'/'.$id.'.stderr.log','');
    $steps[]=['id'=>$id,'group'=>$group,'label'=>$label,'required'=>false,'exit_code'=>null,'status'=>'skip','duration_seconds'=>0.0,'command'=>null,'stdout_log'=>'steps/'.$id.'.stdout.log','stderr_log'=>'steps/'.$id.'.stderr.log','stdout_tail'=>$reason,'stderr_tail'=>''];
};

$recordIssue=static function(string $id,string $label,string $group,string $reason) use(&$steps,$logDir):void{
    file_put_contents($logDir.'/'.$id.'.stdout.log','');
    file_put_contents($logDir.'/'.$id.'.stderr.log',$reason."\n");
    $steps[]=['id'=>$id,'group'=>$group,'label'=>$label,'required'=>true,'exit_code'=>127,'status'=>'fail','duration_seconds'=>0.0,'command'=>null,'stdout_log'=>'steps/'.$id.'.stdout.log','stderr_log'=>'steps/'.$id.'.stderr.log','stdout_tail'=>'','stderr_tail'=>$reason];
};

$environment=[
    'platform_version'=>$version,
    'php_version'=>PHP_VERSION,
    'php_binary'=>PHP_BINARY,
    'php_ini'=>php_ini_loaded_file() ?: null,
    'php_os_family'=>PHP_OS_FAMILY,
    'php_extensions'=>get_loaded_extensions(),
    'composer_json_sha256'=>is_file($root.'/composer.json')?hash_file('sha256',$root.'/composer.json'):null,
    'composer_lock_sha256'=>is_file($root.'/composer.lock')?hash_file('sha256',$root.'/composer.lock'):null,
    'package_json_sha256'=>is_file($root.'/package.json')?hash_file('sha256',$root.'/package.json'):null,
    'package_lock_sha256'=>is_file($root.'/package-lock.json')?hash_file('sha256',$root.'/package-lock.json'):null,
    'composer_available'=>$commandExists('composer'),
    'node_available'=>$commandExists('node'),
    'npm_available'=>$commandExists('npm'),
    'vendor_autoload'=>is_file($root.'/vendor/autoload.php'),
    'node_modules'=>is_dir($root.'/node_modules'),
    'package_lock'=>is_file($root.'/package-lock.json'),
    'public_build_manifest'=>is_file($root.'/public/build/manifest.json'),
];
file_put_contents($runDir.'/environment.json',json_encode($environment,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));

// Group 1: dependency-free source and platform contracts.
$sourceChecks=[
    ['preflight-source','Source certification preflight',[PHP_BINARY,'scripts/certification-preflight.php','--source-only','--json']],
    ['module-graph','Module graph',[PHP_BINARY,'scripts/module-graph-verify.php']],
    ['laravel-contract','Laravel runtime contracts',[PHP_BINARY,'scripts/laravel-runtime-contract-verify.php']],
    ['database-contract','Database contracts',[PHP_BINARY,'scripts/database-contract-verify.php']],
    ['security-contract','Security contracts',[PHP_BINARY,'scripts/security-contract-verify.php']],
    ['zero-install-contract','Zero-install contracts',[PHP_BINARY,'scripts/zero-install-contract-verify.php']],
    ['browser-contract','Browser/UX contracts',[PHP_BINARY,'scripts/browser-ux-contract-verify.php']],
    ['inertia-frontend-contract','Inertia frontend type contracts',[PHP_BINARY,'scripts/inertia-frontend-contract-verify.php']],
    ['target-runtime-contract','Target runtime closure contracts',[PHP_BINARY,'scripts/target-runtime-contract-verify.php']],
    ['target-evidence-contract','Unified target evidence intake contracts',[PHP_BINARY,'scripts/target-evidence-contract-verify.php']],
    ['performance-contract','Performance contracts',[PHP_BINARY,'scripts/performance-contract-verify.php']],
    ['ha-final-contract','HA/final evidence contracts',[PHP_BINARY,'scripts/ha-final-contract-verify.php']],
    ['closure-contract','Final closure contracts',[PHP_BINARY,'scripts/final-closure-contract-verify.php']],
    ['dependency-contract','Dependency/lockfile contracts',[PHP_BINARY,'scripts/dependency-contract-verify.php']],
    ['filesystem-contract','Filesystem/path portability contracts',[PHP_BINARY,'scripts/filesystem-contract-verify.php']],
    ['runtime-safety-contract','Runtime limits/queue/proxy contracts',[PHP_BINARY,'scripts/runtime-safety-contract-verify.php']],
    ['concurrency-contract','Database concurrency/idempotency contracts',[PHP_BINARY,'scripts/concurrency-contract-verify.php']],
    ['source-guard','Source Guard',[PHP_BINARY,'scripts/source-guard.php','--source-only']],
];
foreach($sourceChecks as [$id,$label,$parts]) $run($id,$label,$parts,'source');
$run('preflight-target','Target prerequisite preflight',[PHP_BINARY,'scripts/certification-preflight.php','--json'],'prerequisites');

// Group 2: toolchain identity, without environment/secret dumps.
if($environment['composer_available']) $run('composer-version','Composer version',['composer','--version'],'toolchain'); else $recordIssue('composer-version','Composer version','toolchain','Composer executable not found in PATH.');
if($environment['node_available']) $run('node-version','Node version',['node','--version'],'toolchain'); else $recordIssue('node-version','Node version','toolchain','Node executable not found in PATH.');
if($environment['npm_available']) $run('npm-version','npm version',['npm','--version'],'toolchain'); else $recordIssue('npm-version','npm version','toolchain','npm executable not found in PATH.');
$run('dependency-runtime','Dependency runtime/lockfile compatibility',[PHP_BINARY,'scripts/dependency-runtime-verify.php'],'toolchain');

// Optional dependency installation. Every install log is captured for Laragon troubleshooting.
if($install){
    if(!$environment['composer_lock_sha256']) $recordIssue('composer-lock-required','Composer lockfile','dependencies','composer.lock is required; diagnostics/certification never resolves an unlocked dependency graph.');
    elseif($environment['composer_available']) $run('composer-install','Composer install from lock',['composer','install','--no-interaction','--prefer-dist','--optimize-autoloader','--no-progress'],'dependencies');
    else $recordSkip('composer-install','Composer install','dependencies','Composer executable not found; dependency installation skipped.');
    if(!$environment['package_lock_sha256']) $recordIssue('npm-lock-required','npm lockfile','dependencies','package-lock.json is required; use the maintainer lock refresh workflow before certification.');
    elseif($environment['npm_available']) $run('npm-install','Node dependency install from lock',['npm','ci','--no-audit','--no-fund'],'dependencies');
    else $recordSkip('npm-install','Node dependency install','dependencies','npm executable not found; dependency installation skipped.');
    $environment['vendor_autoload']=is_file($root.'/vendor/autoload.php');
    $environment['node_modules']=is_dir($root.'/node_modules');
    $environment['package_lock']=is_file($root.'/package-lock.json');
    file_put_contents($runDir.'/environment.json',json_encode($environment,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
}

$environment['vendor_autoload']=is_file($root.'/vendor/autoload.php');
$environment['node_modules']=is_dir($root.'/node_modules');
if($environment['vendor_autoload']) $run('vendor-state','Composer dependency state',[PHP_BINARY,'-r','exit(is_file("vendor/autoload.php")?0:1);'],'dependencies');
else $recordIssue('vendor-state','Composer dependency state','dependencies','vendor/autoload.php missing. Run with --install-deps or composer install.');
if($environment['node_modules']) $run('node-modules-state','Node dependency state',[PHP_BINARY,'-r','exit(is_dir("node_modules")?0:1);'],'dependencies');
else $recordIssue('node-modules-state','Node dependency state','dependencies','node_modules missing. Run with --install-deps after package-lock.json exists; certification uses npm ci.');
file_put_contents($runDir.'/environment.json',json_encode($environment,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));

// Group 3: Laravel bootstrap diagnostics. No destructive migration is run unless --full is supplied.
if(is_file($root.'/vendor/autoload.php')){
    foreach([
        ['package-discover','Laravel package discovery',[PHP_BINARY,'artisan','package:discover','--ansi']],
        ['artisan-about','Laravel about',[PHP_BINARY,'artisan','about']],
        ['route-list','Route list',[PHP_BINARY,'artisan','route:list']],
        ['schedule-list','Schedule list',[PHP_BINARY,'artisan','schedule:list']],
        ['optimize-clear','Optimize clear',[PHP_BINARY,'artisan','optimize:clear']],
        ['environment-doctor','Environment/config drift doctor',[PHP_BINARY,'artisan','nexora:environment:doctor']],
        ['filesystem-doctor','Filesystem/path portability doctor',[PHP_BINARY,'artisan','nexora:filesystem:doctor']],
        ['transfer-doctor','Large-file/streaming transfer doctor',[PHP_BINARY,'artisan','nexora:transfer:doctor']],
        ['runtime-doctor','Runtime limits / queue doctor',[PHP_BINARY,'artisan','nexora:runtime:doctor']],
        ['concurrency-doctor','Database concurrency/idempotency doctor',[PHP_BINARY,'artisan','nexora:concurrency:doctor']],
    ] as [$id,$label,$parts]) $run($id,$label,$parts,'laravel');
}else{
    foreach(['package-discover'=>'Laravel package discovery','artisan-about'=>'Laravel about','route-list'=>'Route list','schedule-list'=>'Schedule list','optimize-clear'=>'Optimize clear','environment-doctor'=>'Environment/config drift doctor','filesystem-doctor'=>'Filesystem/path portability doctor','transfer-doctor'=>'Large-file/streaming transfer doctor','runtime-doctor'=>'Runtime limits / queue doctor','concurrency-doctor'=>'Database concurrency/idempotency doctor'] as $id=>$label) $recordSkip($id,$label,'laravel','vendor/autoload.php missing; run Composer install first.');
}

// Group 4: frontend semantic/build diagnostics.
if(is_dir($root.'/node_modules')){
    $run('npm-typecheck','npm run typecheck',['npm','run','typecheck'],'frontend');
    $run('npm-test','npm run test',['npm','run','test'],'frontend');
    $run('npm-build','npm run build',['npm','run','build'],'frontend');
    if(is_file($root.'/public/build/manifest.json')) $run('build-budget','Production build asset budgets',[PHP_BINARY,'scripts/performance-build-verify.php'],'frontend');
    else $recordSkip('build-budget','Production build asset budgets','frontend','public/build/manifest.json missing after build.');
}else{
    foreach(['npm-typecheck'=>'npm run typecheck','npm-test'=>'npm run test','npm-build'=>'npm run build','build-budget'=>'Production build asset budgets'] as $id=>$label) $recordSkip($id,$label,'frontend','node_modules missing; run npm ci from the reviewed package-lock.json first.');
}

// Group 5: full isolated certification is intentionally opt-in because it exercises the certification database.
if($full){
    if(is_file($root.'/vendor/autoload.php') && is_dir($root.'/node_modules')) $run('full-certification','Full isolated certification',[PHP_BINARY,'scripts/certify-release.php','--no-package','--keep-going'],'full');
    else $recordSkip('full-certification','Full isolated certification','full','Dependencies missing; full certification cannot run.');
}else $recordSkip('full-certification','Full isolated certification','full','Not requested. Re-run with --full to exercise certification DB/migrations/tests.');

// Group 6: closure ledger is diagnostic; exit 2 means correctly BLOCKED and is still captured.
$run('closure-status','Final closure status',[PHP_BINARY,'scripts/final-closure-status.php'],'closure');

$issues=array_values(array_filter($steps,static fn(array $step):bool=>$step['status']==='fail' && $step['id']!=='closure-status'));
$firstIssue=$issues[0]??null;
$summary=[
    'schema'=>1,
    'status'=>$issues===[]?'diagnostics-clean':'issues-found',
    'platform_version'=>$version,
    'run_id'=>$runId,
    'started_at'=>gmdate(DATE_ATOM,(int)$started),
    'finished_at'=>gmdate(DATE_ATOM),
    'duration_seconds'=>round(microtime(true)-$started,3),
    'install_dependencies_requested'=>$install,
    'full_certification_requested'=>$full,
    'environment'=>$environment,
    'issue_count'=>count($issues),
    'first_issue'=>$firstIssue===null?null:['id'=>$firstIssue['id'],'label'=>$firstIssue['label'],'exit_code'=>$firstIssue['exit_code'],'stderr_tail'=>$firstIssue['stderr_tail']],
    'steps'=>$steps,
];
file_put_contents($runDir.'/summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));

$md="# Nexora {$version} target diagnostics\n\n";
$md.="- Run ID: `{$runId}`\n- Status: **{$summary['status']}**\n- Issues: **{$summary['issue_count']}**\n- First issue: **".($firstIssue['label']??'none')."**\n- Composer: ".($environment['composer_available']?'available':'missing')."\n- vendor/autoload.php: ".($environment['vendor_autoload']?'yes':'no')."\n- Node/npm: ".(($environment['node_available']&&$environment['npm_available'])?'available':'incomplete')."\n- node_modules: ".($environment['node_modules']?'yes':'no')."\n\n";
$md.="| Group | Step | Status | Exit |\n|---|---|---:|---:|\n";
foreach($steps as $step) $md.='| '.$step['group'].' | '.str_replace('|','\\|',$step['label']).' | '.strtoupper($step['status']).' | '.($step['exit_code']===null?'—':$step['exit_code'])." |\n";
$md.="\nLogs are under `steps/`. Sensitive token/password/cookie-shaped values are redacted as `[REDACTED]`. The collector does not dump `.env` or ambient environment variables.\n";
file_put_contents($runDir.'/summary.md',$md);
file_put_contents($baseDir.'/latest.json',json_encode(['run_id'=>$runId,'platform_version'=>$version,'status'=>$summary['status'],'path'=>$runDir,'updated_at'=>gmdate(DATE_ATOM)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));

$bundle=null;
if(!$noBundle && class_exists(ZipArchive::class)){
    $bundle=$baseDir.'/Nexora_Target_Diagnostics_'.$version.'_'.$runId.'.zip';
    $zip=new ZipArchive();
    if($zip->open($bundle,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true){
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runDir,FilesystemIterator::SKIP_DOTS));
        foreach($iterator as $file){
            if(!$file->isFile()) continue;
            $relative=str_replace('\\','/',substr($file->getPathname(),strlen($runDir)+1));
            $zip->addFile($file->getPathname(),$relative);
        }
        $zip->close();
    }else $bundle=null;
}

fwrite(STDOUT,"\n[Nexora Target Diagnostics] {$summary['status']} — {$version}\n");
fwrite(STDOUT,"Report: {$runDir}/summary.md\n");
if($bundle!==null) fwrite(STDOUT,"Bundle: {$bundle}\n");
elseif(!$noBundle) fwrite(STDOUT,"Bundle: not created (PHP ext-zip unavailable). Send the run directory instead.\n");
fwrite(STDOUT,"Issues captured: {$summary['issue_count']}\n");
exit(0);
