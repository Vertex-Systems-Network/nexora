<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/n1-certified-toolchain.php';

function nexoraCertificationSessionPath(string $root): string
{
    return $root.'/storage/app/nexora/certification/session.json';
}

function nexoraCertificationSessionFinalizationPath(string $root): string
{
    return $root.'/storage/app/nexora/certification/session-finalization.json';
}

/** @return array<string,mixed>|null */
function nexoraCertificationSessionRead(string $root): ?array
{
    $path=nexoraCertificationSessionPath($root);if(!is_file($path))return null;
    try{$data=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(Throwable){return null;}
    return is_array($data)?$data:null;
}

/** @return array<string,mixed>|null */
function nexoraCertificationSessionFinalizationRead(string $root): ?array
{
    $path=nexoraCertificationSessionFinalizationPath($root);if(!is_file($path))return null;
    try{$data=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(Throwable){return null;}
    return is_array($data)?$data:null;
}

/** @return array<string,string|null> */
function nexoraCertificationSessionFingerprint(string $root): array
{
    $hash=static fn(string $path):?string=>is_file($path)?(hash_file('sha256',$path)?:null):null;$source=nexoraComputeSourceAttestation($root);
    return [
        'platform_version'=>(string)((require $root.'/config/nexora.php')['version']??'unknown'),
        'source_tree_sha256'=>(string)$source['tree_sha256'],
        'composer_lock_sha256'=>$hash($root.'/composer.lock'),
        'package_lock_sha256'=>$hash($root.'/package-lock.json'),
        'reviewed_locks_sha256'=>$hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
        'certified_toolchain_sha256'=>$hash(nexoraCertifiedToolchainPath($root)),
    ];
}

/** @return list<string> */
function nexoraCertificationSessionPrerequisiteErrors(string $root): array
{
    $errors=[];$fp=nexoraCertificationSessionFingerprint($root);
    foreach(['composer_lock_sha256','package_lock_sha256','reviewed_locks_sha256','certified_toolchain_sha256'] as $key)if(!is_string($fp[$key]??null)||preg_match('/^[a-f0-9]{64}$/',(string)$fp[$key])!==1)$errors[]="certification session requires {$key}";
    $toolchainErrors=nexoraValidateCertifiedToolchain($root);foreach($toolchainErrors as $error)$errors[]='certified toolchain: '.$error;
    $reviewedPath=$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json';
    if(is_file($reviewedPath)){
        try{$reviewed=json_decode((string)file_get_contents($reviewedPath),true,128,JSON_THROW_ON_ERROR);}catch(Throwable){$reviewed=null;}
        if(!is_array($reviewed)||($reviewed['status']??null)!=='reviewed')$errors[]='certification session requires a valid reviewed-lock attestation';
        else{
            $expected=['composer_lock_sha256'=>$fp['composer_lock_sha256'],'package_lock_sha256'=>$fp['package_lock_sha256'],'composer_manifest_sha256'=>is_file($root.'/composer.json')?hash_file('sha256',$root.'/composer.json'):null,'package_manifest_sha256'=>is_file($root.'/package.json')?hash_file('sha256',$root.'/package.json'):null];
            foreach($expected as $key=>$value)if(!is_string($value)||($reviewed[$key]??null)!==$value)$errors[]="reviewed-lock attestation mismatch [{$key}]";
        }
    }
    return array_values(array_unique($errors));
}

function nexoraCertificationSessionTimestampFresh(string $root,mixed $value): bool
{
    $time=strtotime((string)$value);if($time===false)return false;$config=is_file($root.'/config/nexora-certification-evidence.php')?require $root.'/config/nexora-certification-evidence.php':[];$maxAge=max(1,(int)($config['certification_session_max_age_hours']??168))*3600;$future=max(0,(int)($config['max_future_clock_skew_seconds']??300));$age=time()-$time;return $age>=-$future&&$age<=$maxAge;
}

/** @return list<string> */
function nexoraValidateCertificationSession(string $root,array $data): array
{
    $errors=[];$fp=nexoraCertificationSessionFingerprint($root);if(($data['schema']??null)!==1)$errors[]='certification session schema must be 1';if(($data['status']??null)!=='open')$errors[]='certification session status must be open';if(preg_match('/^[a-f0-9]{32}$/',(string)($data['session_id']??''))!==1)$errors[]='certification session_id must be 32 lowercase hex characters';
    foreach($fp as $key=>$value){if(!is_string($value)||$value==='')$errors[]="current certification fingerprint [{$key}] is unavailable";elseif(($data[$key]??null)!==$value)$errors[]="certification session fingerprint mismatch [{$key}]";}
    if(!nexoraCertificationSessionTimestampFresh($root,$data['created_at']??null))$errors[]='certification session created_at is stale, invalid or too far in the future';return array_merge($errors,nexoraCertificationSessionPrerequisiteErrors($root));
}

/** @return list<string> */
function nexoraValidateCertificationSessionFinalization(string $root,?array $data=null): array
{
    $errors=[];$session=nexoraCertificationSessionRead($root);if(!is_array($session))return ['certification session missing'];if($data===null){$data=nexoraCertificationSessionFinalizationRead($root);if(!is_array($data))return ['certification session finalization missing'];}
    if(($data['schema']??null)!==2||($data['status']??null)!=='finalized')$errors[]='certification session finalization schema/status invalid';if(($data['certification_session_id']??null)!==($session['session_id']??null))$errors[]='certification session finalization ID mismatch';$sessionHash=hash_file('sha256',nexoraCertificationSessionPath($root))?:null;if(($data['certification_session_sha256']??null)!==$sessionHash)$errors[]='certification session finalization session hash mismatch';
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');$fv=preg_replace('/[^0-9A-Za-z._-]+/','-',$version)?:'unknown';$dist=$root.'/dist';$paths=['production_sha256'=>$dist.'/nexora-'.$fv.'-production.zip','evidence_bundle_sha256'=>$dist.'/nexora-'.$fv.'-certification-evidence.zip','release_seal_sha256'=>$dist.'/nexora-'.$fv.'-release-seal.json','release_signature_sha256'=>$dist.'/nexora-'.$fv.'-release-seal.sig','release_public_key_sha256'=>$dist.'/nexora-'.$fv.'-release-public.pem','release_trust_anchor_sha256'=>$root.'/storage/app/nexora/release-signing/trust-anchor.json','dependency_sbom_sha256'=>$root.'/storage/app/nexora/certification/dependency-sbom.json','production_dependencies_sha256'=>$root.'/storage/app/nexora/certification/production-dependencies.json','release_provenance_sha256'=>$root.'/storage/app/nexora/certification/release-provenance.json'];
    foreach($paths as $key=>$path){$actual=is_file($path)?(hash_file('sha256',$path)?:null):null;if(!is_string($actual)||($data[$key]??null)!==$actual)$errors[]="certification session finalization artifact mismatch [{$key}]";}
    $time=strtotime((string)($data['finalized_at']??''));if($time===false||$time>time()+300)$errors[]='certification session finalization timestamp invalid';return $errors;
}

/** @return array<string,mixed> */
function nexoraEnsureCertificationSession(string $root): array
{
    $current=nexoraCertificationSessionRead($root);
    if(is_array($current)&&nexoraValidateCertificationSession($root,$current)===[]){if(nexoraValidateCertificationSessionFinalization($root)===[])throw new RuntimeException('Certification session is already finalized. Start a new source/lock certification cycle before collecting new operator evidence.');return $current;}
    $pre=nexoraCertificationSessionPrerequisiteErrors($root);if($pre!==[])throw new RuntimeException('Unable to create certification session: '.implode('; ',$pre));$fp=nexoraCertificationSessionFingerprint($root);$payload=['schema'=>1,'status'=>'open','session_id'=>bin2hex(random_bytes(16)),'created_at'=>gmdate(DATE_ATOM)]+$fp;
    $dir=dirname(nexoraCertificationSessionPath($root));if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('Unable to create certification session directory.');@unlink(nexoraCertificationSessionFinalizationPath($root));$tmp=nexoraCertificationSessionPath($root).'.tmp.'.bin2hex(random_bytes(4));$json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;if(file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,nexoraCertificationSessionPath($root))){@unlink($tmp);throw new RuntimeException('Unable to atomically publish certification session.');}return $payload;
}

/** @return array<string,mixed> */
function nexoraFinalizeCertificationSession(string $root): array
{
    $session=nexoraCertificationSessionRead($root);if(!is_array($session)||nexoraValidateCertificationSession($root,$session)!==[])throw new RuntimeException('Active certification session is not valid/open.');
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');$fv=preg_replace('/[^0-9A-Za-z._-]+/','-',$version)?:'unknown';$dist=$root.'/dist';$files=['production_sha256'=>$dist.'/nexora-'.$fv.'-production.zip','evidence_bundle_sha256'=>$dist.'/nexora-'.$fv.'-certification-evidence.zip','release_seal_sha256'=>$dist.'/nexora-'.$fv.'-release-seal.json','release_signature_sha256'=>$dist.'/nexora-'.$fv.'-release-seal.sig','release_public_key_sha256'=>$dist.'/nexora-'.$fv.'-release-public.pem','release_trust_anchor_sha256'=>$root.'/storage/app/nexora/release-signing/trust-anchor.json','dependency_sbom_sha256'=>$root.'/storage/app/nexora/certification/dependency-sbom.json','production_dependencies_sha256'=>$root.'/storage/app/nexora/certification/production-dependencies.json','release_provenance_sha256'=>$root.'/storage/app/nexora/certification/release-provenance.json'];$payload=['schema'=>2,'status'=>'finalized','certification_session_id'=>$session['session_id'],'certification_session_sha256'=>hash_file('sha256',nexoraCertificationSessionPath($root))?:null,'finalized_at'=>gmdate(DATE_ATOM)];foreach($files as $key=>$path){$hash=is_file($path)?(hash_file('sha256',$path)?:null):null;if(!is_string($hash))throw new RuntimeException("Unable to finalize certification session: missing {$key}");$payload[$key]=$hash;}
    $path=nexoraCertificationSessionFinalizationPath($root);$tmp=$path.'.tmp.'.bin2hex(random_bytes(4));$json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;if(file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Unable to atomically publish certification session finalization.');}return $payload;
}

/** @return list<string> */
function nexoraValidateEvidenceSessionBinding(string $root,array $data,string $label): array
{
    $session=nexoraCertificationSessionRead($root);if(!is_array($session))return ["{$label} requires an active certification session"];$errors=nexoraValidateCertificationSession($root,$session);if($errors!==[])return array_map(static fn(string $e):string=>"{$label} certification session: {$e}",$errors);if(nexoraValidateCertificationSessionFinalization($root)===[])return ["{$label} cannot be collected after the certification session has been finalized"];$provided=strtolower(trim((string)($data['certification_session_id']??'')));if($provided===''||!hash_equals((string)$session['session_id'],$provided))return ["{$label} certification_session_id does not match the active certification session"];return [];
}
