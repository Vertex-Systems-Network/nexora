<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
$platform=require $root.'/config/nexora.php';
$version=(string)($platform['version']??'unknown');
$requirePass=in_array('--require-pass',$argv,true);
$seal=in_array('--seal',$argv,true);
$input=null;
foreach($argv as $arg) if(str_starts_with($arg,'--input=')) $input=trim(substr($arg,8));
if($input===null||$input===''){
    fwrite(STDERR,"Usage: php scripts/target-runtime-evidence-verify.php --input=<bundle.zip|run-directory> [--require-pass] [--seal]\n"); exit(2);
}
$inputPath=$input;
if(!preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/',$inputPath)) $inputPath=$root.DIRECTORY_SEPARATOR.$inputPath;
if(!file_exists($inputPath)){ fwrite(STDERR,"[Nexora Target Runtime Evidence] Input not found.\n"); exit(2); }

$tempDir=null;$sourceDir=null;$bundleSha=null;
try{
    if(is_dir($inputPath)){
        $sourceDir=rtrim($inputPath,'\\/');
    }else{
        if(!class_exists(ZipArchive::class)){ fwrite(STDERR,"[Nexora Target Runtime Evidence] ext-zip is required to inspect a bundle.\n"); exit(1); }
        $bundleSha=hash_file('sha256',$inputPath)?:null;
        $zip=new ZipArchive();
        if($zip->open($inputPath)!==true){ fwrite(STDERR,"[Nexora Target Runtime Evidence] Unable to open ZIP.\n"); exit(1); }
        for($i=0;$i<$zip->numFiles;$i++){
            $name=(string)$zip->getNameIndex($i);
            $normalized=str_replace('\\','/',$name);
            if($normalized===''||str_starts_with($normalized,'/')||preg_match('/^[A-Za-z]:\//',$normalized)===1||str_contains('/'.$normalized.'/','/../')||str_contains('/'.$normalized.'/','/./')){
                $zip->close(); fwrite(STDERR,"[Nexora Target Runtime Evidence] Unsafe ZIP path [{$name}].\n"); exit(1);
            }
            $opsys=0;$attributes=0;
            if($zip->getExternalAttributesIndex($i,$opsys,$attributes)){
                $mode=($attributes>>16)&0xF000;
                if($mode===0xA000){ $zip->close(); fwrite(STDERR,"[Nexora Target Runtime Evidence] Symbolic-link ZIP entry rejected [{$name}].\n"); exit(1); }
            }
        }
        $tempDir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-target-evidence-'.bin2hex(random_bytes(6));
        if(!mkdir($tempDir,0700,true)&&!is_dir($tempDir)){ $zip->close(); throw new RuntimeException('Unable to create evidence temp directory.'); }
        if(!$zip->extractTo($tempDir)){ $zip->close(); throw new RuntimeException('Unable to extract evidence ZIP.'); }
        $zip->close();$sourceDir=$tempDir;
    }

    foreach(['summary.json','environment.json'] as $required) if(!is_file($sourceDir.DIRECTORY_SEPARATOR.$required)) throw new RuntimeException("Evidence missing {$required}.");
    $summary=json_decode((string)file_get_contents($sourceDir.DIRECTORY_SEPARATOR.'summary.json'),true,512,JSON_THROW_ON_ERROR);
    $environment=json_decode((string)file_get_contents($sourceDir.DIRECTORY_SEPARATOR.'environment.json'),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($summary)||!is_array($environment)) throw new RuntimeException('Evidence JSON payload is invalid.');
    if(($summary['platform_version']??null)!==$version||($environment['platform_version']??null)!==$version) throw new RuntimeException('Evidence platform version does not match current source.');
    $currentSource=nexoraComputeSourceAttestation($root);
    if(($environment['source_tree_sha256']??null)!==$currentSource['tree_sha256']) throw new RuntimeException('Evidence source-tree digest does not match current source.');
    foreach(['composer_lock_sha256'=>'composer.lock','package_lock_sha256'=>'package-lock.json'] as $key=>$relative){
        $expected=$environment[$key]??null;
        if($expected===null) continue;
        if(!is_file($root.'/'.$relative)) throw new RuntimeException("Evidence is lock-bound but current {$relative} is missing.");
        $actual=hash_file('sha256',$root.'/'.$relative)?:null;
        if(!is_string($actual)||!hash_equals((string)$expected,$actual)) throw new RuntimeException("Evidence {$relative} digest does not match current source.");
    }
    $steps=(array)($summary['steps']??[]);
    if($steps===[]) throw new RuntimeException('Evidence contains no runtime steps.');
    $seen=[];
    foreach($steps as $step){
        $id=(string)($step['id']??'');
        if($id===''||preg_match('/^[a-z0-9-]+$/',$id)!==1) throw new RuntimeException('Evidence contains an invalid step id.');
        if(isset($seen[$id])) throw new RuntimeException("Evidence contains duplicate step id [{$id}].");
        $seen[$id]=true;
        foreach(['stdout_log','stderr_log'] as $field){
            $relative=(string)($step[$field]??'');
            if($relative===''||str_contains($relative,'..')||!is_file($sourceDir.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative))) throw new RuntimeException("Evidence step [{$id}] missing {$field}.");
        }
    }
    $status=(string)($summary['status']??'');
    $allowed=['blocked','target-readiness-pass','target-certification-pass'];
    if(!in_array($status,$allowed,true)) throw new RuntimeException("Unknown target runtime evidence status [{$status}].");
    if($requirePass&&!in_array($status,['target-readiness-pass','target-certification-pass'],true)) throw new RuntimeException("Target runtime evidence is not PASS; current status is {$status}.");
    if($status!=='blocked'&&($summary['first_blocker']??null)!==null) throw new RuntimeException('PASS evidence may not contain a first blocker.');

    $result=[
        'schema'=>1,'status'=>'pass','platform_version'=>$version,'verified_at'=>gmdate(DATE_ATOM),
        'runtime_status'=>$status,'run_id'=>(string)($summary['run_id']??''),'source_tree_sha256'=>$currentSource['tree_sha256'],
        'composer_lock_sha256'=>$environment['composer_lock_sha256']??null,'package_lock_sha256'=>$environment['package_lock_sha256']??null,
        'bundle_sha256'=>$bundleSha,'step_count'=>count($steps),'require_pass'=>$requirePass,
    ];
    if($seal){
        $dir=$root.'/storage/app/nexora/certification';
        if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('Unable to create certification directory.');
        file_put_contents($dir.'/target-runtime-evidence.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
    }
    fwrite(STDOUT,"[Nexora Target Runtime Evidence] PASS — {$status}; ".count($steps)." steps; source {$currentSource['tree_sha256']}\n");
    if($seal) fwrite(STDOUT,"Sealed: storage/app/nexora/certification/target-runtime-evidence.json\n");
    exit(0);
}catch(Throwable $e){
    fwrite(STDERR,"[Nexora Target Runtime Evidence] FAIL — {$e->getMessage()}\n"); exit(1);
}finally{
    if($tempDir!==null&&is_dir($tempDir)){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
        foreach($it as $file){ $file->isDir()?@rmdir($file->getPathname()):@unlink($file->getPathname()); }
        @rmdir($tempDir);
    }
}
