<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/n1-certification-session.php';
require_once __DIR__.'/release-artifact.php';
require_once __DIR__.'/release-signature.php';
require_once __DIR__.'/release-trust-anchor.php';
require_once __DIR__.'/release-archive-hygiene.php';
require_once __DIR__.'/n1-certified-toolchain.php';

/** @return array{bundle:string,bundle_sidecar:string,seal:string,signature:string,public_key:string,finalization:string} */
function nexoraFinalReleaseSealPaths(string $root): array
{
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $fv=preg_replace('/[^0-9A-Za-z._-]+/','-',$version)?:'unknown';
    $base=$root.'/dist/nexora-'.$fv;
    return [
        'bundle'=>$base.'-certification-evidence.zip',
        'bundle_sidecar'=>$base.'-certification-evidence.zip.sha256',
        'seal'=>$base.'-release-seal.json',
        'signature'=>$base.'-release-seal.sig',
        'public_key'=>$base.'-release-public.pem',
        'finalization'=>nexoraCertificationSessionFinalizationPath($root),
    ];
}

/** @return array<string,string> */
function nexoraFinalReleaseEvidenceFiles(string $root): array
{
    return [
        'c1'=>'storage/app/nexora/n1-c1/latest.json',
        'c2'=>'storage/app/nexora/n1-c2/latest.json',
        'c3'=>'storage/app/nexora/certification/database-matrix.json',
        'c4'=>'storage/app/nexora/n1-c4/c4-evidence.json',
        'c5'=>'storage/app/nexora/n1-c5/c5-evidence.json',
        'final_evidence'=>'storage/app/nexora/certification/final-evidence.json',
        'target_evidence_intake'=>'storage/app/nexora/certification/target-evidence-intake.json',
        'dependency_audit'=>'storage/app/nexora/certification/dependency-audit.json',
        'dependency_provenance'=>'storage/app/nexora/certification/dependency-provenance.json',
        'dependency_sbom'=>'storage/app/nexora/certification/dependency-sbom.json',
        'production_dependencies'=>'storage/app/nexora/certification/production-dependencies.json',
        'release_provenance'=>'storage/app/nexora/certification/release-provenance.json',
        'release_trust_anchor'=>'storage/app/nexora/release-signing/trust-anchor.json',
        'build_assets'=>'storage/app/nexora/certification/build-assets.json',
        'http_performance'=>'storage/app/nexora/certification/http-performance.json',
        'reviewed_locks'=>'storage/app/nexora/dependency-intake/reviewed-locks.json',
        'certification_session'=>'storage/app/nexora/certification/session.json',
        'certified_toolchain'=>'storage/app/nexora/certification/toolchain.json',
    ];
}

/** @return array{ok:bool,errors:list<string>,bundle_sha256:?string,signature_sha256:?string,seal:?array} */
function nexoraBuildFinalReleaseSeal(string $root,string $productionPath): array
{
    $errors=[];$paths=nexoraFinalReleaseSealPaths($root);
    foreach(['bundle','bundle_sidecar','seal','signature','public_key','finalization'] as $key)@unlink($paths[$key]);
    if(!class_exists(ZipArchive::class))return ['ok'=>false,'errors'=>['PHP ext-zip is required to build the final evidence bundle'],'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];

    $production=nexoraValidateProductionArtifactCore($root,$productionPath);
    if(!$production['ok'])return ['ok'=>false,'errors'=>array_map(static fn($e)=>'production: '.$e,$production['errors']),'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];

    $session=nexoraCertificationSessionRead($root);
    if(!is_array($session))$errors[]='active certification session missing';
    else foreach(nexoraValidateCertificationSession($root,$session) as $e)$errors[]='session: '.$e;

    $anchor=nexoraReleaseTrustAnchorRead($root);
    foreach(nexoraValidateReleaseTrustAnchor($root,$anchor) as $e)$errors[]='trust anchor: '.$e;

    $evidence=[];
    foreach(nexoraFinalReleaseEvidenceFiles($root) as $key=>$relative){
        $path=$root.'/'.$relative;
        if(!is_file($path)){$errors[]="required release evidence missing [{$relative}]";continue;}
        $hash=hash_file('sha256',$path)?:null;
        if(!is_string($hash)){$errors[]="unable to hash release evidence [{$relative}]";continue;}
        $evidence[$key]=['path'=>$relative,'sha256'=>$hash];
    }
    if($errors!==[])return ['ok'=>false,'errors'=>array_values(array_unique($errors)),'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];

    $source=nexoraComputeSourceAttestation($root);
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $toolchainHash=hash_file('sha256',nexoraCertifiedToolchainPath($root))?:null;
    $anchorPath=nexoraReleaseTrustAnchorPath($root);$anchorHash=hash_file('sha256',$anchorPath)?:null;
    $index=[
        'schema'=>4,'status'=>'sealed-index','platform_version'=>$version,
        'source_tree_sha256'=>$source['tree_sha256'],'deployment_generation'=>$production['manifest']['runtime_deployment']['generation']??null,'certification_session_id'=>$session['session_id'],
        'certified_toolchain_sha256'=>$toolchainHash,'release_trust_anchor_sha256'=>$anchorHash,
        'update_trust_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-update-trust.php'),
        'engine_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-engine.php'),
        'network_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-network-runtime.php'),
        'host_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-host-runtime.php'),
        'resource_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-resource-runtime.php'),
        'policy_plane_sha256'=>hash_file('sha256',$root.'/config/nexora-policy-runtime.php'),
        'process_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-process-runtime.php'),
        'framework_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-framework.php'),
        'signing_key_id'=>$anchor['key_id'],'signing_public_key_sha256'=>$anchor['public_key_sha256'],
        'created_at'=>gmdate(DATE_ATOM),'evidence'=>$evidence,
    ];
    $zip=new ZipArchive();
    if($zip->open($paths['bundle'],ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)return ['ok'=>false,'errors'=>['unable to create certification evidence bundle'],'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];
    $zip->addFromString('evidence-index.json',json_encode($index,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
    foreach($evidence as $key=>$row)$zip->addFile($root.'/'.$row['path'],'manifests/'.$key.'.json');
    $zip->close();
    $hygiene=nexoraValidateZipArchiveHygiene($root,$paths['bundle']);
    if($hygiene!==[]){@unlink($paths['bundle']);return ['ok'=>false,'errors'=>array_map(static fn($e)=>'evidence bundle hygiene: '.$e,$hygiene),'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];}
    $bundleHash=hash_file('sha256',$paths['bundle'])?:null;
    if(!is_string($bundleHash))return ['ok'=>false,'errors'=>['unable to hash certification evidence bundle'],'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];
    file_put_contents($paths['bundle_sidecar'],$bundleHash.'  '.basename($paths['bundle']).PHP_EOL);

    $trust=nexoraReleaseSigningConfig($root);$publicInfo=null;
    if(is_file($trust['public']))$publicInfo=nexoraReleasePublicKeyInfo((string)file_get_contents($trust['public']));
    if($trust['required']&&!is_array($publicInfo))return ['ok'=>false,'errors'=>['release signing is required but the configured public key is missing'],'bundle_sha256'=>$bundleHash,'signature_sha256'=>null,'seal'=>null];
    if(is_array($publicInfo)&&!hash_equals((string)$anchor['public_key_sha256'],$publicInfo['public_sha256']))return ['ok'=>false,'errors'=>['configured release public key does not match active trust anchor'],'bundle_sha256'=>$bundleHash,'signature_sha256'=>null,'seal'=>null];

    $seal=[
        'schema'=>4,'status'=>'sealed','platform_version'=>$version,'source_tree_sha256'=>$source['tree_sha256'],'deployment_generation'=>$production['manifest']['runtime_deployment']['generation']??null,
        'certification_session_id'=>$session['session_id'],'certification_session_sha256'=>hash_file('sha256',nexoraCertificationSessionPath($root))?:null,
        'certified_toolchain_sha256'=>$toolchainHash,'created_at'=>gmdate(DATE_ATOM),
        'production'=>['file'=>basename($productionPath),'sha256'=>$production['sha256']],
        'evidence_bundle'=>['file'=>basename($paths['bundle']),'sha256'=>$bundleHash],
        'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),
        'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json'),
        'reviewed_locks_sha256'=>hash_file('sha256',$root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
        'final_evidence_sha256'=>hash_file('sha256',$root.'/storage/app/nexora/certification/final-evidence.json'),
        'dependency_sbom_sha256'=>hash_file('sha256',$root.'/storage/app/nexora/certification/dependency-sbom.json'),
        'production_dependencies_sha256'=>hash_file('sha256',$root.'/storage/app/nexora/certification/production-dependencies.json'),
        'release_provenance_sha256'=>hash_file('sha256',$root.'/storage/app/nexora/certification/release-provenance.json'),
        'update_trust_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-update-trust.php'),
        'engine_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-engine.php'),
        'network_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-network-runtime.php'),
        'host_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-host-runtime.php'),
        'resource_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-resource-runtime.php'),
        'policy_plane_sha256'=>hash_file('sha256',$root.'/config/nexora-policy-runtime.php'),
        'process_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-process-runtime.php'),
        'framework_policy_sha256'=>hash_file('sha256',$root.'/config/nexora-framework.php'),
        'trust_anchor_sha256'=>$anchorHash,
        'signing'=>[
            'required'=>$trust['required'],'algorithm'=>$trust['algorithm'],'key_id'=>$anchor['key_id'],
            'public_key_sha256'=>$publicInfo['public_sha256']??null,'public_key_fingerprint'=>$publicInfo['fingerprint']??null,
        ],
    ];
    file_put_contents($paths['seal'],json_encode($seal,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);

    $signatureSha=null;
    if($trust['required']||is_file($trust['private'])||is_file($trust['public'])){
        $signed=nexoraSignReleaseSeal($root,$paths['seal'],$paths['signature'],$paths['public_key']);
        if(!$signed['ok'])return ['ok'=>false,'errors'=>array_map(static fn($e)=>'release signature: '.$e,$signed['errors']),'bundle_sha256'=>$bundleHash,'signature_sha256'=>null,'seal'=>$seal];
        $signatureSha=$signed['signature_sha256'];
        if(($seal['signing']['public_key_sha256']??null)!==$signed['public_sha256'])return ['ok'=>false,'errors'=>['release signing public key changed during seal construction'],'bundle_sha256'=>$bundleHash,'signature_sha256'=>$signatureSha,'seal'=>$seal];
    }
    return ['ok'=>true,'errors'=>[],'bundle_sha256'=>$bundleHash,'signature_sha256'=>$signatureSha,'seal'=>$seal];
}

/** @return array{ok:bool,errors:list<string>,production_sha256:?string,bundle_sha256:?string,signature_sha256:?string,seal:?array} */
function nexoraValidateFinalReleaseSeal(string $root,?string $productionPath=null): array
{
    $paths=nexoraFinalReleaseSealPaths($root);$errors=[];$seal=null;$productionHash=null;$bundleHash=null;$signatureHash=null;
    if(!is_file($paths['seal']))return ['ok'=>false,'errors'=>['final release seal missing'],'production_sha256'=>null,'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];
    try{$seal=json_decode((string)file_get_contents($paths['seal']),true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){return ['ok'=>false,'errors'=>['invalid final release seal JSON: '.$e->getMessage()],'production_sha256'=>null,'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];}
    if(!is_array($seal))return ['ok'=>false,'errors'=>['final release seal must be an object'],'production_sha256'=>null,'bundle_sha256'=>null,'signature_sha256'=>null,'seal'=>null];
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');$source=nexoraComputeSourceAttestation($root);$session=nexoraCertificationSessionRead($root);
    if(($seal['schema']??null)!==4||($seal['status']??null)!=='sealed')$errors[]='release seal schema/status invalid';
    if(($seal['platform_version']??null)!==$version)$errors[]='release seal platform_version mismatch';
    if(($seal['source_tree_sha256']??null)!==$source['tree_sha256'])$errors[]='release seal source-tree mismatch';
    if(!is_array($session))$errors[]='active certification session missing';else{
        foreach(nexoraValidateCertificationSession($root,$session) as $e)$errors[]='session: '.$e;
        if(($seal['certification_session_id']??null)!==($session['session_id']??null))$errors[]='release seal certification_session_id mismatch';
        $sessionHash=hash_file('sha256',nexoraCertificationSessionPath($root))?:null;if(($seal['certification_session_sha256']??null)!==$sessionHash)$errors[]='release seal certification session SHA mismatch';
    }
    $toolchainPath=nexoraCertifiedToolchainPath($root);$toolchainHash=is_file($toolchainPath)?(hash_file('sha256',$toolchainPath)?:null):null;
    if(!is_string($toolchainHash)||($seal['certified_toolchain_sha256']??null)!==$toolchainHash)$errors[]='release seal certified toolchain mismatch';
    foreach(nexoraValidateCertifiedToolchain($root) as $e)$errors[]='toolchain: '.$e;

    $anchor=nexoraReleaseTrustAnchorRead($root);foreach(nexoraValidateReleaseTrustAnchor($root,$anchor) as $e)$errors[]='trust anchor: '.$e;
    $anchorHash=is_file(nexoraReleaseTrustAnchorPath($root))?(hash_file('sha256',nexoraReleaseTrustAnchorPath($root))?:null):null;
    if(($seal['trust_anchor_sha256']??null)!==$anchorHash)$errors[]='release seal trust-anchor SHA mismatch';
    if(is_array($anchor)){
        if(($seal['signing']['key_id']??null)!==($anchor['key_id']??null))$errors[]='release seal signing key_id mismatch';
        if(($seal['signing']['public_key_sha256']??null)!==($anchor['public_key_sha256']??null))$errors[]='release seal signing public-key SHA mismatch';
    }

    if($productionPath===null){$fv=preg_replace('/[^0-9A-Za-z._-]+/','-',$version)?:'unknown';$productionPath=$root.'/dist/nexora-'.$fv.'-production.zip';}
    if(!is_file($productionPath))$errors[]='sealed production ZIP missing';else{$productionHash=hash_file('sha256',$productionPath)?:null;if(($seal['production']['sha256']??null)!==$productionHash)$errors[]='release seal production ZIP hash mismatch';}
    if(is_file($productionPath)&&class_exists(ZipArchive::class)){$pz=new ZipArchive();if($pz->open($productionPath,ZipArchive::RDONLY)===true){$raw=$pz->getFromName('nexora-release.json');$pz->close();if(is_string($raw)){try{$pm=json_decode($raw,true,256,JSON_THROW_ON_ERROR);}catch(Throwable){$pm=null;}if(is_array($pm)&&($seal['deployment_generation']??null)!==($pm['runtime_deployment']['generation']??null))$errors[]='release seal deployment generation mismatch';}}}
    if(!is_file($paths['bundle']))$errors[]='certification evidence bundle missing';else{$bundleHash=hash_file('sha256',$paths['bundle'])?:null;if(($seal['evidence_bundle']['sha256']??null)!==$bundleHash)$errors[]='release seal evidence-bundle hash mismatch';}

    if(!is_file($paths['signature'])||!is_file($paths['public_key']))$errors[]='release detached signature/public key missing';else{
        $signatureHash=hash_file('sha256',$paths['signature'])?:null;
        foreach(nexoraVerifyDetachedReleaseSignature($paths['seal'],$paths['signature'],$paths['public_key'],is_array($anchor)?(string)$anchor['public_key_sha256']:null) as $e)$errors[]='signature: '.$e;
    }
    $updatePolicyHash=is_file($root.'/config/nexora-update-trust.php')?(hash_file('sha256',$root.'/config/nexora-update-trust.php')?:null):null;if(($seal['update_trust_policy_sha256']??null)!==$updatePolicyHash)$errors[]='release seal update-trust policy mismatch';$enginePolicyHash=is_file($root.'/config/nexora-engine.php')?(hash_file('sha256',$root.'/config/nexora-engine.php')?:null):null;if(($seal['engine_policy_sha256']??null)!==$enginePolicyHash)$errors[]='release seal runtime-engine policy mismatch';$networkPolicyHash=is_file($root.'/config/nexora-network-runtime.php')?(hash_file('sha256',$root.'/config/nexora-network-runtime.php')?:null):null;if(($seal['network_policy_sha256']??null)!==$networkPolicyHash)$errors[]='release seal service/network policy mismatch';$hostPolicyHash=is_file($root.'/config/nexora-host-runtime.php')?(hash_file('sha256',$root.'/config/nexora-host-runtime.php')?:null):null;if(($seal['host_policy_sha256']??null)!==$hostPolicyHash)$errors[]='release seal host/clock policy mismatch';$resourcePolicyHash=is_file($root.'/config/nexora-resource-runtime.php')?(hash_file('sha256',$root.'/config/nexora-resource-runtime.php')?:null):null;if(($seal['resource_policy_sha256']??null)!==$resourcePolicyHash)$errors[]='release seal resource-envelope policy mismatch';$policyPlaneHash=is_file($root.'/config/nexora-policy-runtime.php')?(hash_file('sha256',$root.'/config/nexora-policy-runtime.php')?:null):null;if(($seal['policy_plane_sha256']??null)!==$policyPlaneHash)$errors[]='release seal effective policy-plane mismatch';$processPolicyHash=is_file($root.'/config/nexora-process-runtime.php')?(hash_file('sha256',$root.'/config/nexora-process-runtime.php')?:null):null;if(($seal['process_policy_sha256']??null)!==$processPolicyHash)$errors[]='release seal runtime process-plane policy mismatch';$frameworkPolicyHash=is_file($root.'/config/nexora-framework.php')?(hash_file('sha256',$root.'/config/nexora-framework.php')?:null):null;if(($seal['framework_policy_sha256']??null)!==$frameworkPolicyHash)$errors[]='release seal framework/dependency policy mismatch';
    foreach(['dependency_sbom_sha256'=>'storage/app/nexora/certification/dependency-sbom.json','production_dependencies_sha256'=>'storage/app/nexora/certification/production-dependencies.json','release_provenance_sha256'=>'storage/app/nexora/certification/release-provenance.json'] as $key=>$rel){$actual=is_file($root.'/'.$rel)?(hash_file('sha256',$root.'/'.$rel)?:null):null;if(($seal[$key]??null)!==$actual)$errors[]="release seal {$key} mismatch";}

    if(is_file($paths['bundle'])&&class_exists(ZipArchive::class)){
        $z=new ZipArchive();if($z->open($paths['bundle'],ZipArchive::RDONLY)!==true)$errors[]='certification evidence bundle cannot be opened';else{
            $raw=$z->getFromName('evidence-index.json');if(!is_string($raw))$errors[]='evidence-index.json missing from certification evidence bundle';else{
                try{$idx=json_decode($raw,true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){$idx=null;$errors[]='invalid evidence-index.json: '.$e->getMessage();}
                if(is_array($idx)){
                    if(($idx['schema']??null)!==4)$errors[]='evidence index schema mismatch';
                    if(($idx['platform_version']??null)!==$version)$errors[]='evidence index version mismatch';
                    if(($idx['source_tree_sha256']??null)!==$source['tree_sha256'])$errors[]='evidence index source mismatch';if(($idx['deployment_generation']??null)!==($seal['deployment_generation']??null))$errors[]='evidence index deployment generation mismatch';
                    if(($idx['certified_toolchain_sha256']??null)!==$toolchainHash)$errors[]='evidence index toolchain mismatch';
                    if(($idx['release_trust_anchor_sha256']??null)!==$anchorHash)$errors[]='evidence index trust-anchor mismatch';
                    if(($idx['update_trust_policy_sha256']??null)!==$updatePolicyHash)$errors[]='evidence index update-trust policy mismatch';if(($idx['engine_policy_sha256']??null)!==$enginePolicyHash)$errors[]='evidence index runtime-engine policy mismatch';if(($idx['network_policy_sha256']??null)!==$networkPolicyHash)$errors[]='evidence index service/network policy mismatch';if(($idx['host_policy_sha256']??null)!==$hostPolicyHash)$errors[]='evidence index host/clock policy mismatch';if(($idx['resource_policy_sha256']??null)!==$resourcePolicyHash)$errors[]='evidence index resource-envelope policy mismatch';if(($idx['policy_plane_sha256']??null)!==$policyPlaneHash)$errors[]='evidence index effective policy-plane mismatch';if(($idx['process_policy_sha256']??null)!==$processPolicyHash)$errors[]='evidence index runtime process-plane policy mismatch';if(($idx['framework_policy_sha256']??null)!==$frameworkPolicyHash)$errors[]='evidence index framework/dependency policy mismatch';
                    if(is_array($session)&&($idx['certification_session_id']??null)!==($session['session_id']??null))$errors[]='evidence index session mismatch';
                    foreach(nexoraFinalReleaseEvidenceFiles($root) as $key=>$relative){$host=$root.'/'.$relative;$expected=is_file($host)?hash_file('sha256',$host):null;$recorded=$idx['evidence'][$key]['sha256']??null;if(!is_string($expected)||$recorded!==$expected)$errors[]="evidence index host binding mismatch [{$key}]";$copy=$z->getFromName('manifests/'.$key.'.json');if(!is_string($copy)||!is_string($expected)||hash('sha256',$copy)!==$expected)$errors[]="evidence bundle manifest mismatch [{$key}]";}
                }
            }$z->close();
        }
    }
    return ['ok'=>$errors===[],'errors'=>array_values(array_unique($errors)),'production_sha256'=>$productionHash,'bundle_sha256'=>$bundleHash,'signature_sha256'=>$signatureHash,'seal'=>$seal];
}
