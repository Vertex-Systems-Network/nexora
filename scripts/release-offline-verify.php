<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/release-signature.php';
require_once $root.'/scripts/lib/release-archive-hygiene.php';
require_once $root.'/scripts/lib/release-content-manifest.php';
require_once $root.'/scripts/lib/deployment-generation.php';
$args=[];foreach($argv as $arg){foreach(['production','evidence','seal','signature','public-key','trust-anchor','expected-public-key-sha256','expected-key-id'] as $key)if(str_starts_with($arg,'--'.$key.'='))$args[$key]=substr($arg,strlen($key)+3);}
if(!isset($args['production'],$args['evidence'],$args['seal'],$args['signature'],$args['public-key'])){fwrite(STDERR,"Usage: php scripts/release-offline-verify.php --production=<zip> --evidence=<zip> --seal=<json> --signature=<sig> --public-key=<pem> (--expected-public-key-sha256=<64hex>|--trust-anchor=<json>) [--expected-key-id=<id>]\n");exit(2);}
foreach(['production','evidence','seal','signature','public-key'] as $key){$path=$args[$key];if(!is_file($path)){fwrite(STDERR,"[Nexora Offline Release Verify] FAIL — missing {$key} [{$path}]\n");exit(1);}}
if(!class_exists(ZipArchive::class)){fwrite(STDERR,"[Nexora Offline Release Verify] FAIL — PHP ext-zip required.\n");exit(1);}
$errors=[];$trustedSha=strtolower(trim((string)($args['expected-public-key-sha256']??'')));$trustedKeyId=trim((string)($args['expected-key-id']??''));
if(isset($args['trust-anchor'])){
    if(!is_file($args['trust-anchor']))$errors[]='external trust anchor file missing';else{try{$a=json_decode((string)file_get_contents($args['trust-anchor']),true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){$a=null;$errors[]='invalid external trust anchor JSON: '.$e->getMessage();}if(is_array($a)){if(($a['status']??null)!=='active')$errors[]='external trust anchor is not active';$sha=strtolower((string)($a['public_key_sha256']??''));if(preg_match('/^[a-f0-9]{64}$/',$sha)!==1)$errors[]='external trust anchor public_key_sha256 invalid';elseif($trustedSha!==''&&!hash_equals($trustedSha,$sha))$errors[]='explicit expected public key does not match external trust anchor';else $trustedSha=$sha;$anchorKey=(string)($a['key_id']??'');if($trustedKeyId!==''&&$anchorKey!==$trustedKeyId)$errors[]='expected key_id does not match external trust anchor';elseif($trustedKeyId===''&&$anchorKey!=='')$trustedKeyId=$anchorKey;}}
}
if($trustedSha===''||preg_match('/^[a-f0-9]{64}$/',$trustedSha)!==1)$errors[]='external trust anchor required: supply --expected-public-key-sha256=<64hex> or --trust-anchor=<trusted-json>'; 
try{$seal=json_decode((string)file_get_contents($args['seal']),true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){$seal=null;$errors[]='invalid release seal JSON: '.$e->getMessage();}
if(!is_array($seal))$errors[]='release seal must be an object';
if(is_array($seal)){
    if(($seal['schema']??null)!==4||($seal['status']??null)!=='sealed')$errors[]='release seal schema/status invalid';
    $prodHash=hash_file('sha256',$args['production'])?:null;$evidenceHash=hash_file('sha256',$args['evidence'])?:null;
    if(($seal['production']['sha256']??null)!==$prodHash)$errors[]='production ZIP hash does not match signed seal';
    if(($seal['evidence_bundle']['sha256']??null)!==$evidenceHash)$errors[]='evidence bundle hash does not match signed seal';
    if($trustedSha!==''&&!hash_equals($trustedSha,strtolower((string)($seal['signing']['public_key_sha256']??''))))$errors[]='signed release public key is not the externally trusted signer';
    if($trustedKeyId!==''&&($seal['signing']['key_id']??null)!==$trustedKeyId)$errors[]='signed release key_id does not match externally trusted signer identity';
    foreach(nexoraVerifyDetachedReleaseSignature($args['seal'],$args['signature'],$args['public-key'],$trustedSha!==''?$trustedSha:null) as $e)$errors[]='signature: '.$e;
}
foreach(nexoraValidateZipArchiveHygiene($root,$args['production']) as $e)$errors[]='production hygiene: '.$e;
foreach(nexoraValidateZipArchiveHygiene($root,$args['evidence']) as $e)$errors[]='evidence hygiene: '.$e;

$prodZip=new ZipArchive();
if($prodZip->open($args['production'],ZipArchive::RDONLY)!==true)$errors[]='production ZIP cannot be opened';else{
    foreach(nexoraValidateReleaseContentManifest($prodZip) as $e)$errors[]='production content manifest: '.$e;
    $raw=$prodZip->getFromName('nexora-release.json');$sbomRaw=$prodZip->getFromName('nexora-sbom.json');$provRaw=$prodZip->getFromName('nexora-provenance.json');
    if(!is_string($raw))$errors[]='nexora-release.json missing';else{try{$manifest=json_decode($raw,true,256,JSON_THROW_ON_ERROR);}catch(Throwable $e){$manifest=null;$errors[]='invalid nexora-release.json: '.$e->getMessage();}if(is_array($manifest)&&is_array($seal)){
        if(($manifest['schema']??null)!==4)$errors[]='production manifest schema mismatch';
        if(($manifest['version']??null)!==($seal['platform_version']??null))$errors[]='production manifest version does not match signed seal';
        if(($manifest['certification']['source_tree_sha256']??null)!==($seal['source_tree_sha256']??null))$errors[]='production manifest source digest does not match signed seal';
        $rd=is_array($manifest['runtime_deployment']??null)?$manifest['runtime_deployment']:null;if(!is_array($rd))$errors[]='production runtime deployment identity missing';else{$computed=nexoraDeploymentGeneration((array)($rd['materials']??[]));if(($rd['generation']??null)!==$computed)$errors[]='production runtime deployment generation invalid';if(($seal['deployment_generation']??null)!==($rd['generation']??null))$errors[]='signed seal deployment generation mismatch';}
        if(($manifest['certification']['certified_toolchain_sha256']??null)!==($seal['certified_toolchain_sha256']??null))$errors[]='production manifest toolchain digest does not match signed seal';
        if(($manifest['artifacts']['composer_lock_sha256']??null)!==($seal['composer_lock_sha256']??null))$errors[]='production manifest Composer lock digest mismatch';
        if(($manifest['artifacts']['package_lock_sha256']??null)!==($seal['package_lock_sha256']??null))$errors[]='production manifest npm lock digest mismatch';
        if(($manifest['update_trust_policy_sha256']??null)!==($seal['update_trust_policy_sha256']??null))$errors[]='production manifest update-trust policy mismatch';
        if(!is_string($sbomRaw)||hash('sha256',$sbomRaw)!==($manifest['artifacts']['dependency_sbom_sha256']??null))$errors[]='production SBOM hash mismatch';
        if(!is_string($provRaw)||hash('sha256',$provRaw)!==($manifest['artifacts']['release_provenance_sha256']??null))$errors[]='production provenance hash mismatch';
    }}
    // Production runtime must not contain Composer dev-only packages.
    $lockRaw=$prodZip->getFromName('composer.lock');$installedRaw=$prodZip->getFromName('vendor/composer/installed.json');
    if(!is_string($lockRaw)||!is_string($installedRaw))$errors[]='production Composer lock/installed metadata missing';else{try{$lock=json_decode($lockRaw,true,512,JSON_THROW_ON_ERROR);$installed=json_decode($installedRaw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$lock=$installed=null;$errors[]='invalid Composer metadata in production archive: '.$e->getMessage();}if(is_array($lock)&&is_array($installed)){$prod=[];foreach((array)($lock['packages']??[]) as $p)if(is_array($p)&&isset($p['name']))$prod[(string)$p['name']]=true;$dev=[];foreach((array)($lock['packages-dev']??[]) as $p)if(is_array($p)&&isset($p['name'])&&!isset($prod[(string)$p['name']]))$dev[(string)$p['name']]=true;$rows=is_array($installed['packages']??null)?$installed['packages']:$installed;foreach((array)$rows as $p)if(is_array($p)&&isset($p['name'])&&isset($dev[(string)$p['name']]))$errors[]='dev-only Composer package present in production archive ['.(string)$p['name'].']';}}
    $prodZip->close();
}
$evZip=new ZipArchive();if($evZip->open($args['evidence'],ZipArchive::RDONLY)!==true)$errors[]='evidence bundle cannot be opened';else{
    $raw=$evZip->getFromName('evidence-index.json');if(!is_string($raw))$errors[]='evidence-index.json missing';else{try{$index=json_decode($raw,true,256,JSON_THROW_ON_ERROR);}catch(Throwable $e){$index=null;$errors[]='invalid evidence-index.json: '.$e->getMessage();}if(is_array($index)&&is_array($seal)){
        if(($index['schema']??null)!==4)$errors[]='evidence index schema mismatch';
        foreach(['platform_version','source_tree_sha256','deployment_generation','certification_session_id','certified_toolchain_sha256'] as $key)if(($index[$key]??null)!==($seal[$key]??null))$errors[]="evidence index mismatch [{$key}]";
        if(($index['signing_key_id']??null)!==($seal['signing']['key_id']??null))$errors[]='evidence index signing key_id mismatch';
        if(($index['signing_public_key_sha256']??null)!==($seal['signing']['public_key_sha256']??null))$errors[]='evidence index signing public key mismatch';
        $required=['c1','c2','c3','c4','c5','final_evidence','target_evidence_intake','dependency_audit','dependency_provenance','dependency_sbom','production_dependencies','release_provenance','release_trust_anchor','build_assets','http_performance','reviewed_locks','certification_session','certified_toolchain'];
        foreach($required as $key){$record=$index['evidence'][$key]??null;if(!is_array($record)||!preg_match('/^[a-f0-9]{64}$/',(string)($record['sha256']??''))){$errors[]="evidence index missing/invalid [{$key}]";continue;}$copy=$evZip->getFromName('manifests/'.$key.'.json');if(!is_string($copy)||hash('sha256',$copy)!==($record['sha256']??null))$errors[]="evidence manifest hash mismatch [{$key}]";}
        $finalRaw=$evZip->getFromName('manifests/final_evidence.json');if(is_string($finalRaw)){try{$final=json_decode($finalRaw,true,128,JSON_THROW_ON_ERROR);}catch(Throwable){$final=null;}if(!is_array($final)||($final['status']??null)!=='pass'||($final['source_tree_sha256']??null)!==($seal['source_tree_sha256']??null)||($final['certification_session_id']??null)!==($seal['certification_session_id']??null)||($final['certified_toolchain_sha256']??null)!==($seal['certified_toolchain_sha256']??null))$errors[]='final evidence manifest is not a PASS bound to the signed source/session/toolchain';}
        $anchorRaw=$evZip->getFromName('manifests/release_trust_anchor.json');if(!is_string($anchorRaw)||hash('sha256',$anchorRaw)!==($seal['trust_anchor_sha256']??null))$errors[]='bundled release trust anchor hash mismatch';
    }}$evZip->close();
}
if($errors!==[]){fwrite(STDERR,"[Nexora Offline Release Verify] FAIL\n - ".implode("\n - ",array_values(array_unique($errors)))."\n");exit(1);}
fwrite(STDOUT,"[Nexora Offline Release Verify] PASS — signed production release matches the externally pinned signer identity, content manifest and certification evidence.\nTrusted public key SHA-256: {$trustedSha}\n");
