<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-evidence-intake.php';
require_once $root.'/app/Nexora/Foundation/Filesystem/AtomicFileWriter.php';

$input='';$seal=in_array('--seal',$argv,true);$requireComplete=in_array('--require-complete',$argv,true);$json=in_array('--json',$argv,true);
foreach($argv as $arg)if(str_starts_with($arg,'--input='))$input=trim(substr($arg,8));
if($input===''){fwrite(STDERR,"Usage: php scripts/target-evidence-intake.php --input=<directory-or-zip> [--seal] [--require-complete] [--json]\n");exit(2);}

try{
    $fingerprint=nexoraTargetEvidenceFingerprint($root);$found=nexoraDiscoverTargetEvidence($input);$results=[];$errors=[];$warnings=[];
    $lockErrors=nexoraValidateReviewedLockAttestation($root);
    if($lockErrors!==[]){if($seal)$errors=array_merge($errors,$lockErrors);else foreach($lockErrors as $e)$warnings[]=$e;}
    $known=nexoraTargetEvidenceKnownFiles();
    foreach($known as $kind=>$filename){
        if(!isset($found[$kind])){$results[$kind]=['status'=>'missing','file'=>$filename,'sha256'=>null,'errors'=>[]];continue;}
        $data=nexoraEvidenceJson($found[$kind]);$fileErrors=$data===null?['invalid JSON']:nexoraValidateTargetEvidenceFile($root,$kind,$data);
        $results[$kind]=['status'=>$fileErrors===[]?'pass':'fail','file'=>$filename,'sha256'=>hash_file('sha256',$found[$kind])?:null,'errors'=>$fileErrors];
        foreach($fileErrors as $e)$errors[]="{$kind}: {$e}";
    }
    $operatorKinds=['zero_install','upgrade_rehearsal','browser','backup_restore','multi_node_ha'];
    if($requireComplete)foreach($operatorKinds as $kind)if(($results[$kind]['status']??null)!=='pass')$errors[]="required operator evidence [{$kind}] is not PASS";
    if($seal&&$errors===[]){
        $destDir=$root.'/storage/app/nexora/certification';$writer=new App\Nexora\Foundation\Filesystem\AtomicFileWriter();$writer->ensureDirectory($destDir);
        $sealable=['zero_install','upgrade_rehearsal','browser','backup_restore','multi_node_ha'];
        foreach($results as $kind=>$row)if(in_array($kind,$sealable,true)&&($row['status']??null)==='pass'&&isset($found[$kind])){$contents=(string)file_get_contents($found[$kind]);$writer->write($destDir.'/'.$known[$kind],$contents,0755,0640);}
    }
    $closure=nexoraEvaluateFinalClosure($root);if($seal)nexoraWriteClosureStatus($root,$closure);
    $status=$errors===[]?'pass':'fail';
    $payload=['schema'=>1,'status'=>$status,'mode'=>$seal?'seal':'inspect','checked_at'=>gmdate(DATE_ATOM),'input'=>basename(rtrim($input,'/\\')),'fingerprint'=>$fingerprint,'reviewed_locks_status'=>$lockErrors===[]?'pass':'fail','evidence'=>$results,'closure_status'=>$closure['status'],'blocking_domains'=>$closure['blocking_domains'],'errors'=>$errors,'warnings'=>$warnings];
    if($seal&&$errors===[]){$writer??=new App\Nexora\Foundation\Filesystem\AtomicFileWriter();$writer->write($root.'/storage/app/nexora/certification/target-evidence-intake.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL,0755,0640);}
    if($json)fwrite(STDOUT,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);else{
        fwrite(STDOUT,"[Nexora Target Evidence Intake] ".strtoupper($status)." — {$fingerprint['platform_version']}\n");
        foreach($results as $kind=>$row)fwrite(STDOUT,sprintf(" - %-24s %-7s %s\n",$kind,strtoupper((string)$row['status']),(string)$row['file']));
        foreach($warnings as $w)fwrite(STDOUT," WARNING: {$w}\n");foreach($errors as $e)fwrite(STDERR," - {$e}\n");
        fwrite(STDOUT,"Closure: {$closure['status']}".($closure['blocking_domains']!==[]?' — blocking '.implode(', ',$closure['blocking_domains']):'')."\n");
    }
    exit($errors===[]?0:1);
}catch(Throwable $e){fwrite(STDERR,"[Nexora Target Evidence Intake] FAIL — {$e->getMessage()}\n");exit(1);}
