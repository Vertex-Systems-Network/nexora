<?php

declare(strict_types=1);
require_once __DIR__.'/release-signature.php';

function nexoraReleaseTrustAnchorPath(string $root): string
{
    return $root.'/storage/app/nexora/release-signing/trust-anchor.json';
}
/** @return array<string,mixed>|null */
function nexoraReleaseTrustAnchorRead(string $root): ?array
{
    $path=nexoraReleaseTrustAnchorPath($root);if(!is_file($path))return null;
    try{$d=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(Throwable){return null;}
    return is_array($d)?$d:null;
}
/** @return list<string> */
function nexoraValidateReleaseTrustAnchor(string $root,?array $anchor=null): array
{
    $errors=[];$anchor=$anchor??nexoraReleaseTrustAnchorRead($root);if(!is_array($anchor))return ['release trust anchor missing'];
    if(($anchor['schema']??null)!==1)$errors[]='release trust anchor schema must be 1';
    if(($anchor['status']??null)!=='active')$errors[]='release trust anchor must be active';
    if(preg_match('/^[A-Za-z0-9._-]{3,64}$/',(string)($anchor['key_id']??''))!==1)$errors[]='release trust anchor key_id invalid';
    $sha=strtolower((string)($anchor['public_key_sha256']??''));if(preg_match('/^[a-f0-9]{64}$/',$sha)!==1)$errors[]='release trust anchor public_key_sha256 invalid';
    $cfg=nexoraReleaseSigningConfig($root);if(!is_file($cfg['public']))$errors[]='configured release public key missing';else{$info=nexoraReleasePublicKeyInfo((string)file_get_contents($cfg['public']));if($sha!==''&&!hash_equals($sha,$info['public_sha256']))$errors[]='release trust anchor does not match configured public key';if(($anchor['fingerprint']??null)!==$info['fingerprint'])$errors[]='release trust anchor fingerprint mismatch';}
    $registered=strtotime((string)($anchor['registered_at']??''));if($registered===false||$registered>time()+300)$errors[]='release trust anchor registered_at invalid';
    return array_values(array_unique($errors));
}
/** @return array<string,mixed> */
function nexoraRegisterReleaseTrustAnchor(string $root,string $keyId): array
{
    $existing=nexoraReleaseTrustAnchorRead($root);if(is_array($existing))throw new RuntimeException('Release trust anchor already exists. Revoke/rotate explicitly; registration never overwrites an existing anchor.');
    $cfg=nexoraReleaseSigningConfig($root);if(!is_file($cfg['public']))throw new RuntimeException('Configured release public key missing.');
    if(preg_match('/^[A-Za-z0-9._-]{3,64}$/',$keyId)!==1)throw new RuntimeException('key_id must be 3-64 characters: letters, numbers, dot, underscore or hyphen.');
    $info=nexoraReleasePublicKeyInfo((string)file_get_contents($cfg['public']));$version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $payload=['schema'=>1,'status'=>'active','key_id'=>$keyId,'public_key_sha256'=>$info['public_sha256'],'fingerprint'=>$info['fingerprint'],'registered_at'=>gmdate(DATE_ATOM),'platform_version_at_registration'=>$version];
    $path=nexoraReleaseTrustAnchorPath($root);$dir=dirname($path);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Unable to create release trust-anchor directory.');$tmp=$path.'.tmp.'.bin2hex(random_bytes(4));$json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;if(file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Unable to atomically publish release trust anchor.');}@chmod($path,0644);return $payload;
}
/** @return array<string,mixed> */
function nexoraRevokeReleaseTrustAnchor(string $root,string $reason): array
{
    $anchor=nexoraReleaseTrustAnchorRead($root);if(!is_array($anchor))throw new RuntimeException('Release trust anchor missing.');if(($anchor['status']??null)==='revoked')return $anchor;
    $anchor['status']='revoked';$anchor['revoked_at']=gmdate(DATE_ATOM);$anchor['revocation_reason']=trim($reason)!==''?trim($reason):'operator revocation';$path=nexoraReleaseTrustAnchorPath($root);$tmp=$path.'.tmp.'.bin2hex(random_bytes(4));$json=json_encode($anchor,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;if(file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Unable to atomically publish trust-anchor revocation.');}return $anchor;
}
