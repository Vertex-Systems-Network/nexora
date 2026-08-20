<?php

declare(strict_types=1);

require_once __DIR__.'/release-signature.php';
require_once __DIR__.'/release-archive-hygiene.php';
require_once __DIR__.'/release-content-manifest.php';

function nexoraUpdateTrustBase(string $root): string { return $root.'/storage/app/nexora/update-trust'; }
function nexoraUpdateTrustAnchorPath(string $root): string { return nexoraUpdateTrustBase($root).'/trusted-anchor.json'; }
function nexoraUpdateAdmissionPath(string $root): string { return nexoraUpdateTrustBase($root).'/admission.json'; }
function nexoraUpdateTrustHistoryPath(string $root): string { return nexoraUpdateTrustBase($root).'/trust-history'; }

function nexoraUpdateStageRecordsPath(string $root): string { return $root.'/storage/app/nexora/update-trust/stage-records'; }
function nexoraUpdateStageRecordId(string $destination): string { return hash('sha256',str_replace('\\','/',rtrim($destination,'/\\'))); }
function nexoraUpdateStageRecordPath(string $root,string $destination): string { return nexoraUpdateStageRecordsPath($root).'/'.nexoraUpdateStageRecordId($destination).'.json'; }
/** @param array<string,mixed> $payload @return array<string,mixed> */
function nexoraPublishUpdateStageRecord(string $root,string $destination,array $payload): array
{
    $dir=nexoraUpdateStageRecordsPath($root);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Unable to create update stage-record directory.');
    $payload=['schema'=>1,'destination'=>$destination,...$payload,'updated_at'=>gmdate(DATE_ATOM)];$path=nexoraUpdateStageRecordPath($root,$destination);$tmp=$path.'.tmp.'.bin2hex(random_bytes(4));
    if(file_put_contents($tmp,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL,LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Unable to publish update stage record.');}@chmod($path,0600);return $payload;
}

/** @return array<string,mixed>|null */
function nexoraUpdateTrustReadJson(string $path): ?array
{
    if(!is_file($path)) return null;
    try{$v=json_decode((string)file_get_contents($path),true,256,JSON_THROW_ON_ERROR);}catch(Throwable){return null;}
    return is_array($v)?$v:null;
}

/** @return list<string> */
function nexoraValidateRecipientTrustAnchor(?array $anchor): array
{
    if(!is_array($anchor)) return ['recipient update trust anchor missing'];
    $errors=[];
    if(($anchor['schema']??null)!==1)$errors[]='recipient trust anchor schema must be 1';
    if(($anchor['status']??null)!=='active')$errors[]='recipient trust anchor must be active';
    if(preg_match('/^[A-Za-z0-9._-]{3,64}$/',(string)($anchor['key_id']??''))!==1)$errors[]='recipient trust anchor key_id invalid';
    if(preg_match('/^[a-f0-9]{64}$/',strtolower((string)($anchor['public_key_sha256']??'')))!==1)$errors[]='recipient trust anchor public_key_sha256 invalid';
    $t=strtotime((string)($anchor['registered_at']??''));if($t===false||$t>time()+300)$errors[]='recipient trust anchor registered_at invalid';
    return array_values(array_unique($errors));
}

/** @return array<string,mixed> */
function nexoraImportRecipientTrustAnchor(string $root,string $source,bool $rotate=false): array
{
    $incoming=nexoraUpdateTrustReadJson($source);$errors=nexoraValidateRecipientTrustAnchor($incoming);if($errors!==[])throw new RuntimeException(implode('; ',$errors));
    $base=nexoraUpdateTrustBase($root);if(!is_dir($base)&&!mkdir($base,0700,true)&&!is_dir($base))throw new RuntimeException('Unable to create update-trust directory.');
    $target=nexoraUpdateTrustAnchorPath($root);$existing=nexoraUpdateTrustReadJson($target);
    if(is_array($existing)&&!$rotate)throw new RuntimeException('Recipient update trust anchor already exists. Use explicit rotation; imports never overwrite silently.');
    if(is_array($existing)&&$rotate){
        $history=nexoraUpdateTrustHistoryPath($root);if(!is_dir($history)&&!mkdir($history,0700,true)&&!is_dir($history))throw new RuntimeException('Unable to create update trust history directory.');
        $stamp=gmdate('YmdHis');$existingHash=hash_file('sha256',$target)?:null;$old=json_encode([...$existing,'persisted_anchor_sha256'=>$existingHash,'rotated_at'=>gmdate(DATE_ATOM),'replacement_key_id'=>$incoming['key_id']??null,'replacement_public_key_sha256'=>$incoming['public_key_sha256']??null],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
        if(file_put_contents($history.'/'.$stamp.'-'.preg_replace('/[^A-Za-z0-9._-]/','-',(string)($existing['key_id']??'old')).'.json',$old,LOCK_EX)===false)throw new RuntimeException('Unable to archive previous update trust anchor.');
    }
    $previousHash=is_file($target)?(hash_file('sha256',$target)?:null):null;$previousSequence=is_array($existing)?max(1,(int)($existing['rotation_sequence']??1)):0;$payload=$incoming;$payload['rotation_sequence']=$previousSequence+1;$payload['previous_anchor_sha256']=$previousHash;$payload['imported_at']=gmdate(DATE_ATOM);$payload['source_anchor_sha256']=hash_file('sha256',$source)?:null;$tmp=$target.'.tmp.'.bin2hex(random_bytes(4));
    if(file_put_contents($tmp,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL,LOCK_EX)===false||!rename($tmp,$target)){@unlink($tmp);throw new RuntimeException('Unable to atomically publish recipient update trust anchor.');}@chmod($target,0600);return $payload;
}


/** @return array<string,mixed> */
function nexoraRevokeRecipientTrustAnchor(string $root,string $reason): array
{
    $path=nexoraUpdateTrustAnchorPath($root);$anchor=nexoraUpdateTrustReadJson($path);if(!is_array($anchor))throw new RuntimeException('Recipient update trust anchor missing.');
    if(($anchor['status']??null)==='revoked')return $anchor;$anchor['status']='revoked';$anchor['revoked_at']=gmdate(DATE_ATOM);$anchor['revocation_reason']=trim($reason)!==''?trim($reason):'operator revocation';
    $history=nexoraUpdateTrustHistoryPath($root);if(!is_dir($history)&&!mkdir($history,0700,true)&&!is_dir($history))throw new RuntimeException('Unable to create update trust history directory.');
    $copy=$history.'/'.gmdate('YmdHis').'-revoked-'.preg_replace('/[^A-Za-z0-9._-]/','-',(string)($anchor['key_id']??'key')).'.json';$json=json_encode($anchor,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;if(file_put_contents($copy,$json,LOCK_EX)===false)throw new RuntimeException('Unable to archive recipient trust-anchor revocation.');$tmp=$path.'.tmp.'.bin2hex(random_bytes(4));if(file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Unable to publish recipient trust-anchor revocation.');}return $anchor;
}
/** @return array{ok:bool,errors:list<string>,depth:int,head_sha256:?string} */
function nexoraVerifyRecipientTrustLineage(string $root): array
{
    $errors=[];$activePath=nexoraUpdateTrustAnchorPath($root);$active=nexoraUpdateTrustReadJson($activePath);if(!is_array($active))return ['ok'=>false,'errors'=>['recipient trust anchor missing'],'depth'=>0,'head_sha256'=>null];
    $head=hash_file('sha256',$activePath)?:null;$sequence=max(0,(int)($active['rotation_sequence']??0));if($sequence<1)$errors[]='recipient trust anchor rotation_sequence missing or invalid';
    $previous=$active['previous_anchor_sha256']??null;$history=[];foreach(glob(nexoraUpdateTrustHistoryPath($root).'/*.json')?:[] as $file){$row=nexoraUpdateTrustReadJson($file);if(is_array($row)&&preg_match('/^[a-f0-9]{64}$/',(string)($row['persisted_anchor_sha256']??''))===1)$history[(string)$row['persisted_anchor_sha256']]=$row;}
    $depth=1;$expectedSequence=$sequence-1;$nextKey=(string)($active['key_id']??'');$nextSha=strtolower((string)($active['public_key_sha256']??''));$seen=[];
    while(is_string($previous)&&$previous!==''){
        if(isset($seen[$previous])){$errors[]='recipient trust-anchor lineage contains a cycle';break;}$seen[$previous]=true;$row=$history[$previous]??null;if(!is_array($row)){$errors[]='recipient trust-anchor lineage history is incomplete';break;}
        if((int)($row['rotation_sequence']??0)!==$expectedSequence)$errors[]='recipient trust-anchor rotation sequence is not contiguous';
        if(($row['replacement_key_id']??null)!==$nextKey||strtolower((string)($row['replacement_public_key_sha256']??''))!==$nextSha)$errors[]='recipient trust-anchor replacement linkage is inconsistent';
        $nextKey=(string)($row['key_id']??'');$nextSha=strtolower((string)($row['public_key_sha256']??''));$previous=$row['previous_anchor_sha256']??null;$expectedSequence--; $depth++;
        if($depth>256){$errors[]='recipient trust-anchor lineage exceeds safety depth';break;}
    }
    if($sequence>0&&$depth!==$sequence)$errors[]='recipient trust-anchor lineage depth does not match rotation_sequence';
    return ['ok'=>$errors===[],'errors'=>array_values(array_unique($errors)),'depth'=>$depth,'head_sha256'=>$head];
}

/** @return array<string,mixed>|null */
function nexoraInstalledMetadataWithoutLaravel(string $root): ?array
{
    $path=$root.'/storage/app/nexora/installed.lock';return nexoraUpdateTrustReadJson($path);
}

/** @return array{ok:bool,errors:list<string>,seal:?array,receipt:?array} */
function nexoraAdmitTrustedUpdate(string $root,array $paths): array
{
    $errors=[];foreach(['production','evidence','seal','signature','public_key'] as $k){if(!isset($paths[$k])||!is_file((string)$paths[$k]))$errors[]="missing update artifact [{$k}]";}
    $anchor=nexoraUpdateTrustReadJson(nexoraUpdateTrustAnchorPath($root));foreach(nexoraValidateRecipientTrustAnchor($anchor) as $e)$errors[]=$e;
    $lineage=nexoraVerifyRecipientTrustLineage($root);foreach($lineage['errors'] as $e)$errors[]='recipient trust lineage: '.$e;
    if($errors!==[])return ['ok'=>false,'errors'=>array_values(array_unique($errors)),'seal'=>null,'receipt'=>null];
    $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/scripts/release-offline-verify.php').' --production='.escapeshellarg((string)$paths['production']).' --evidence='.escapeshellarg((string)$paths['evidence']).' --seal='.escapeshellarg((string)$paths['seal']).' --signature='.escapeshellarg((string)$paths['signature']).' --public-key='.escapeshellarg((string)$paths['public_key']).' --trust-anchor='.escapeshellarg(nexoraUpdateTrustAnchorPath($root));$out=[];$code=0;exec($cmd.' 2>&1',$out,$code);if($code!==0)return ['ok'=>false,'errors'=>['strict offline release verification failed: '.implode(' | ',$out)],'seal'=>null,'receipt'=>null];
    if(!class_exists(ZipArchive::class))return ['ok'=>false,'errors'=>['PHP ext-zip required to admit a signed update'],'seal'=>null,'receipt'=>null];
    try{$seal=json_decode((string)file_get_contents((string)$paths['seal']),true,256,JSON_THROW_ON_ERROR);}catch(Throwable $e){return ['ok'=>false,'errors'=>['invalid release seal JSON: '.$e->getMessage()],'seal'=>null,'receipt'=>null];}
    if(!is_array($seal))return ['ok'=>false,'errors'=>['release seal must be an object'],'seal'=>null,'receipt'=>null];
    if(($seal['schema']??null)!==4||($seal['status']??null)!=='sealed')$errors[]='trusted updates require release seal schema 4';
    $trustedSha=strtolower((string)($anchor['public_key_sha256']??''));$keyId=(string)($anchor['key_id']??'');
    if(($seal['signing']['key_id']??null)!==$keyId)$errors[]='release signer key_id does not match recipient trust anchor';
    if(!hash_equals($trustedSha,strtolower((string)($seal['signing']['public_key_sha256']??''))))$errors[]='release signer public key does not match recipient trust anchor';
    foreach(nexoraVerifyDetachedReleaseSignature((string)$paths['seal'],(string)$paths['signature'],(string)$paths['public_key'],$trustedSha) as $e)$errors[]='signature: '.$e;
    foreach(nexoraValidateZipArchiveHygiene($root,(string)$paths['production']) as $e)$errors[]='production hygiene: '.$e;
    foreach(nexoraValidateZipArchiveHygiene($root,(string)$paths['evidence']) as $e)$errors[]='evidence hygiene: '.$e;
    $ph=hash_file('sha256',(string)$paths['production'])?:null;$eh=hash_file('sha256',(string)$paths['evidence'])?:null;
    $productionManifest=null;$productionZip=new ZipArchive();if($productionZip->open((string)$paths['production'],ZipArchive::RDONLY)!==true)$errors[]='production ZIP cannot be opened for runtime deployment identity';else{$releaseRaw=$productionZip->getFromName('nexora-release.json');$productionZip->close();if(!is_string($releaseRaw))$errors[]='production release manifest missing during update admission';else{try{$productionManifest=json_decode($releaseRaw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$errors[]='invalid production release manifest during update admission: '.$e->getMessage();}}}
    $targetGeneration=is_array($productionManifest)?strtolower(trim((string)($productionManifest['runtime_deployment']['generation']??''))):'';$targetFrontend=is_array($productionManifest)?strtolower(trim((string)($productionManifest['artifacts']['frontend_manifest_sha256']??''))):'';if(preg_match('/^[a-f0-9]{64}$/',$targetGeneration)!==1)$errors[]='production release runtime deployment generation missing/invalid';if(preg_match('/^[a-f0-9]{64}$/',$targetFrontend)!==1)$errors[]='production release frontend manifest SHA missing/invalid';
    if(($seal['production']['sha256']??null)!==$ph)$errors[]='production ZIP hash does not match signed seal';
    if(($seal['evidence_bundle']['sha256']??null)!==$eh)$errors[]='evidence bundle hash does not match signed seal';
    $installed=nexoraInstalledMetadataWithoutLaravel($root);$source=trim((string)($installed['version']??''));$target=trim((string)($seal['platform_version']??''));
    if($source===''||preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/',$source)!==1)$errors[]='installed metadata does not contain a valid source version';
    if($target===''||preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/',$target)!==1)$errors[]='signed release target version is invalid';
    if($source!==''&&$target!==''&&version_compare($target,$source,'<'))$errors[]="rollback blocked: target {$target} is older than installed {$source}";
    $policy=require $root.'/config/nexora-update-trust.php';if($source!==''&&$target!==''&&version_compare($target,$source,'==')&&!(bool)($policy['allow_reinstall_same_version']??false))$errors[]="same-version reinstall blocked by policy: {$target}";
    $priorSeal=strtolower((string)($installed['release_seal_sha256']??''));if($priorSeal!==''&&preg_match('/^[a-f0-9]{64}$/',$priorSeal)!==1)$errors[]='installed release lineage seal hash is invalid';
    if($errors!==[])return ['ok'=>false,'errors'=>array_values(array_unique($errors)),'seal'=>$seal,'receipt'=>null];
    $ttl=max(15,(int)($policy['admission_ttl_minutes']??180));$receipt=['schema'=>1,'status'=>'admitted','admission_kind'=>'signed-release','source_version'=>$source,'target_version'=>$target,'target_source_tree_sha256'=>$seal['source_tree_sha256']??null,'target_deployment_generation'=>$targetGeneration,'target_frontend_manifest_sha256'=>$targetFrontend,'signer_key_id'=>$keyId,'signer_public_key_sha256'=>$trustedSha,'recipient_trust_anchor_sha256'=>hash_file('sha256',nexoraUpdateTrustAnchorPath($root))?:null,'trust_lineage_head_sha256'=>$lineage['head_sha256']??null,'trust_lineage_depth'=>$lineage['depth']??null,'production_sha256'=>$ph,'evidence_sha256'=>$eh,'seal_sha256'=>hash_file('sha256',(string)$paths['seal'])?:null,'signature_sha256'=>hash_file('sha256',(string)$paths['signature'])?:null,'public_key_sha256'=>hash_file('sha256',(string)$paths['public_key'])?:null,'previous_installed_release_seal_sha256'=>$priorSeal!==''?$priorSeal:null,'admitted_at'=>gmdate(DATE_ATOM),'expires_at'=>gmdate(DATE_ATOM,time()+$ttl*60)];
    $base=nexoraUpdateTrustBase($root);if(!is_dir($base)&&!mkdir($base,0700,true)&&!is_dir($base))return ['ok'=>false,'errors'=>['unable to create update trust directory'],'seal'=>$seal,'receipt'=>null];$targetPath=nexoraUpdateAdmissionPath($root);$tmp=$targetPath.'.tmp.'.bin2hex(random_bytes(4));if(file_put_contents($tmp,json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL,LOCK_EX)===false||!rename($tmp,$targetPath)){@unlink($tmp);return ['ok'=>false,'errors'=>['unable to publish update admission receipt'],'seal'=>$seal,'receipt'=>null];}@chmod($targetPath,0600);return ['ok'=>true,'errors'=>[],'seal'=>$seal,'receipt'=>$receipt];
}
