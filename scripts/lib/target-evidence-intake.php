<?php

declare(strict_types=1);

require_once __DIR__.'/final-evidence.php';
require_once __DIR__.'/final-closure.php';
require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/n1-certified-toolchain.php';

/** @return array<string,string> */
function nexoraTargetEvidenceKnownFiles(): array
{
    return [
        'automated_certification'=>'latest.json',
        'build_assets'=>'build-assets.json',
        'http_performance'=>'http-performance.json',
        'database_matrix'=>'database-matrix.json',
        'zero_install'=>'zero-install-evidence.json',
        'upgrade_rehearsal'=>'upgrade-rehearsal-evidence.json',
        'browser'=>'browser-evidence.json',
        'backup_restore'=>'backup-restore-evidence.json',
        'multi_node_ha'=>'ha-evidence.json',
        'target_runtime'=>'target-runtime-evidence.json',
    ];
}

/** @return array<string,mixed> */
function nexoraTargetEvidenceFingerprint(string $root): array
{
    $source=nexoraComputeSourceAttestation($root);
    $hash=static fn(string $relative):?string=>is_file($root.'/'.$relative)?(hash_file('sha256',$root.'/'.$relative)?:null):null;
    return [
        'platform_version'=>(string)((require $root.'/config/nexora.php')['version']??'unknown'),
        'source_tree_sha256'=>(string)$source['tree_sha256'],
        'composer_lock_sha256'=>$hash('composer.lock'),
        'package_lock_sha256'=>$hash('package-lock.json'),
        'reviewed_locks_sha256'=>$hash('storage/app/nexora/dependency-intake/reviewed-locks.json'),
        'certified_toolchain_sha256'=>$hash('storage/app/nexora/certification/toolchain.json'),
    ];
}

/** @return list<string> */
function nexoraValidateReviewedLockAttestation(string $root): array
{
    $path=$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json';
    $data=nexoraEvidenceJson($path);$errors=[];
    if($data===null) return ['reviewed-lock attestation is missing or invalid'];
    if(($data['status']??null)!=='reviewed')$errors[]='reviewed-lock attestation status must be reviewed';
    $expected=[
        'composer_manifest_sha256'=>is_file($root.'/composer.json')?hash_file('sha256',$root.'/composer.json'):null,
        'package_manifest_sha256'=>is_file($root.'/package.json')?hash_file('sha256',$root.'/package.json'):null,
        'composer_lock_sha256'=>is_file($root.'/composer.lock')?hash_file('sha256',$root.'/composer.lock'):null,
        'package_lock_sha256'=>is_file($root.'/package-lock.json')?hash_file('sha256',$root.'/package-lock.json'):null,
    ];
    foreach($expected as $key=>$value){if(!is_string($value)||$value==='')$errors[]="current dependency artifact for {$key} is missing";elseif(($data[$key]??null)!==$value)$errors[]="reviewed-lock attestation mismatch [{$key}]";}
    return $errors;
}

/** @return list<string> */
function nexoraValidateTargetEvidenceFile(string $root,string $kind,array $data): array
{
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    return match($kind){
        'zero_install'=>nexoraValidateZeroInstallEvidence($root,$data),
        'upgrade_rehearsal'=>nexoraValidateUpgradeRehearsalEvidence($root,$data),
        'browser'=>nexoraValidateBrowserEvidenceForFinal($root,$data),
        'backup_restore'=>nexoraValidateBackupRestoreEvidence($root,$data),
        'multi_node_ha'=>nexoraValidateHaEvidence($root,$data),
        'database_matrix'=>nexoraValidateDatabaseMatrixEvidence($root,$data),
        'build_assets','http_performance'=>array_merge(
            ($data['status']??null)==='pass'?[]:["{$kind} status must be pass"],
            ($data['platform_version']??null)===$version?[]:["{$kind} platform_version mismatch"],
            nexoraValidateEvidenceSourceBinding($root,$data,$kind)
        ),
        'automated_certification'=>array_merge(
            ($data['status']??null)==='certification-pass'?[]:['automated certification status must be certification-pass'],
            ($data['platform_version']??null)===$version?[]:['automated certification platform_version mismatch'],
            nexoraValidateEvidenceSourceBinding($root,$data,'automated certification')
        ),
        'target_runtime'=>array_merge(
            ($data['status']??null)==='pass'?[]:['target runtime evidence verifier status must be pass'],
            in_array(($data['runtime_status']??null),['target-readiness-pass','target-certification-pass'],true)?[]:['target runtime evidence must represent a PASS runtime status'],
            ($data['platform_version']??null)===$version?[]:['target runtime platform_version mismatch'],
            nexoraValidateEvidenceSourceBinding($root,$data,'target runtime evidence')
        ),
        default=>['unsupported evidence kind'],
    };
}

/** @return array<string,string> */
function nexoraDiscoverTargetEvidence(string $input): array
{
    $known=nexoraTargetEvidenceKnownFiles();$found=[];
    if(is_dir($input)){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input,FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){if(!$file->isFile()||$file->isLink())continue;$base=$file->getBasename();foreach($known as $kind=>$name)if($base===$name&&!isset($found[$kind]))$found[$kind]=$file->getPathname();}
        return $found;
    }
    if(!is_file($input))throw new RuntimeException('Evidence input path does not exist.');
    if(strtolower(pathinfo($input,PATHINFO_EXTENSION))!=='zip')throw new RuntimeException('Evidence input must be a directory or ZIP bundle.');
    if(!class_exists(ZipArchive::class))throw new RuntimeException('PHP ext-zip is required to inspect evidence ZIP bundles.');
    if(filesize($input)>64*1024*1024)throw new RuntimeException('Evidence ZIP exceeds the 64 MiB intake ceiling.');
    $zip=new ZipArchive();if($zip->open($input)!==true)throw new RuntimeException('Unable to open evidence ZIP.');
    if($zip->numFiles>500){$zip->close();throw new RuntimeException('Evidence ZIP exceeds 500 entries.');}
    $tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-evidence-'.bin2hex(random_bytes(8));if(!mkdir($tmp,0700,true)&&!is_dir($tmp)){$zip->close();throw new RuntimeException('Unable to create evidence intake temp directory.');}
    register_shutdown_function(static function()use($tmp):void{if(!is_dir($tmp))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $f){$f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname());}@rmdir($tmp);});
    $wanted=array_flip(array_values($known));
    for($i=0;$i<$zip->numFiles;$i++){
        $stat=$zip->statIndex($i);$name=str_replace('\\','/',(string)($stat['name']??''));if($name===''||str_contains($name,"\0")||str_starts_with($name,'/')||preg_match('/^[A-Za-z]:\//',$name)||in_array('..',explode('/',$name),true)){ $zip->close();throw new RuntimeException("Unsafe evidence ZIP path [{$name}]."); }
        $opsys=0;$attrs=0;if(method_exists($zip,'getExternalAttributesIndex')){@$zip->getExternalAttributesIndex($i,$opsys,$attrs);}
        if((($attrs>>16)&0170000)===0120000){$zip->close();throw new RuntimeException("Evidence ZIP symbolic link rejected [{$name}].");}
        $base=basename($name);if(!isset($wanted[$base]))continue;
        foreach($found as $existingKind=>$existingPath)if(basename($existingPath)===$base){$zip->close();throw new RuntimeException("Duplicate recognized evidence file rejected [{$base}].");}
        if((int)($stat['size']??0)>8*1024*1024){$zip->close();throw new RuntimeException("Evidence JSON exceeds 8 MiB [{$base}].");}
        $contents=$zip->getFromIndex($i);if(!is_string($contents)){ $zip->close();throw new RuntimeException("Unable to read evidence [{$base}]."); }
        $dest=$tmp.DIRECTORY_SEPARATOR.$base;file_put_contents($dest,$contents);
        foreach($known as $kind=>$filename)if($filename===$base&&!isset($found[$kind]))$found[$kind]=$dest;
    }
    $zip->close();return $found;
}

/** @return list<string> */
function nexoraValidateTargetEvidenceIntakeManifest(string $root,array $data): array
{
    $errors=[];$current=nexoraTargetEvidenceFingerprint($root);
    if(($data['schema']??null)!==1)$errors[]='target evidence intake schema must be 1';
    if(($data['status']??null)!=='pass')$errors[]='target evidence intake status must be pass';
    $fp=(array)($data['fingerprint']??[]);
    foreach(['platform_version','source_tree_sha256','composer_lock_sha256','package_lock_sha256','reviewed_locks_sha256','certified_toolchain_sha256'] as $key){
        if(($fp[$key]??null)!==($current[$key]??null))$errors[]="target evidence intake fingerprint mismatch [{$key}]";
    }
    foreach(['zero_install','upgrade_rehearsal','browser','backup_restore','multi_node_ha'] as $kind){
        if(($data['evidence'][$kind]['status']??null)!=='pass')$errors[]="target evidence intake operator domain [{$kind}] must be pass";
        $file=nexoraTargetEvidenceKnownFiles()[$kind];$path=$root.'/storage/app/nexora/certification/'.$file;
        if(!is_file($path)){$errors[]="target evidence intake sealed file missing [{$file}]";continue;}
        $sha=hash_file('sha256',$path)?:null;if(($data['evidence'][$kind]['sha256']??null)!==$sha)$errors[]="target evidence intake hash mismatch [{$kind}]";
    }
    return array_merge($errors,nexoraValidateReviewedLockAttestation($root));
}
