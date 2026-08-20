<?php

declare(strict_types=1);

function nexoraReleaseTrustPath(string $root,string $configured): string
{
    if($configured==='')return '';
    if(str_starts_with($configured,'/')||preg_match('/^[A-Za-z]:[\\\\\/]/',$configured)===1)return $configured;
    return $root.'/'.str_replace(['\\','/'],DIRECTORY_SEPARATOR,$configured);
}

/** @return array{private:string,public:string,required:bool,bits:int,algorithm:string} */
function nexoraReleaseSigningConfig(string $root): array
{
    $config=require $root.'/config/nexora-release-trust.php';
    return ['private'=>nexoraReleaseTrustPath($root,(string)($config['private_key_path']??'')),'public'=>nexoraReleaseTrustPath($root,(string)($config['public_key_path']??'')),'required'=>(bool)($config['signature_required']??true),'bits'=>(int)($config['rsa_bits']??3072),'algorithm'=>(string)($config['signature_algorithm']??'sha256WithRSA')];
}

/** @return array{public_pem:string,public_sha256:string,fingerprint:string} */
function nexoraReleasePublicKeyInfo(string $publicPem): array
{
    $normalized=str_replace("\r\n","\n",trim($publicPem))."\n";$sha=hash('sha256',$normalized);
    return ['public_pem'=>$normalized,'public_sha256'=>$sha,'fingerprint'=>implode(':',str_split(strtoupper(substr($sha,0,32)),2))];
}

/** @return array{ok:bool,errors:list<string>,signature_sha256:?string,public_sha256:?string,fingerprint:?string} */
function nexoraSignReleaseSeal(string $root,string $sealPath,string $signaturePath,string $publicCopyPath): array
{
    $cfg=nexoraReleaseSigningConfig($root);$errors=[];
    if(!extension_loaded('openssl'))return ['ok'=>false,'errors'=>['PHP OpenSSL extension is required for release signing'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    if(!is_file($cfg['private']))$errors[]='release signing private key missing';if(!is_file($cfg['public']))$errors[]='release signing public key missing';if(!is_file($sealPath))$errors[]='release seal missing before signing';if($errors!==[])return ['ok'=>false,'errors'=>$errors,'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    $privatePem=(string)file_get_contents($cfg['private']);$publicPem=(string)file_get_contents($cfg['public']);$private=openssl_pkey_get_private($privatePem);$public=openssl_pkey_get_public($publicPem);if($private===false||$public===false)return ['ok'=>false,'errors'=>['unable to load configured release signing key pair'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    $privDetails=openssl_pkey_get_details($private);$pubDetails=openssl_pkey_get_details($public);if(!is_array($privDetails)||!is_array($pubDetails)||($privDetails['type']??null)!==OPENSSL_KEYTYPE_RSA||($pubDetails['type']??null)!==OPENSSL_KEYTYPE_RSA)return ['ok'=>false,'errors'=>['release signing key pair must be RSA'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    if(($privDetails['bits']??0)<$cfg['bits']||($pubDetails['bits']??0)<$cfg['bits'])return ['ok'=>false,'errors'=>['release signing RSA key is below configured minimum bits'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    $info=nexoraReleasePublicKeyInfo($publicPem);if(!hash_equals($info['public_sha256'],nexoraReleasePublicKeyInfo((string)$pubDetails['key'])['public_sha256']))return ['ok'=>false,'errors'=>['configured public key normalization mismatch'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    $contents=(string)file_get_contents($sealPath);$signature='';if(!openssl_sign($contents,$signature,$private,OPENSSL_ALGO_SHA256))return ['ok'=>false,'errors'=>['OpenSSL failed to sign release seal'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];if(openssl_verify($contents,$signature,$public,OPENSSL_ALGO_SHA256)!==1)return ['ok'=>false,'errors'=>['configured release signing private/public keys do not form a valid pair'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    $dir=dirname($signaturePath);if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))return ['ok'=>false,'errors'=>['unable to create release signature directory'],'signature_sha256'=>null,'public_sha256'=>null,'fingerprint'=>null];
    file_put_contents($signaturePath,base64_encode($signature).PHP_EOL);file_put_contents($publicCopyPath,$info['public_pem']);$sigSha=hash_file('sha256',$signaturePath)?:null;
    return ['ok'=>is_string($sigSha),'errors'=>is_string($sigSha)?[]:['unable to hash release signature'],'signature_sha256'=>$sigSha,'public_sha256'=>$info['public_sha256'],'fingerprint'=>$info['fingerprint']];
}

/** @return list<string> */
function nexoraVerifyDetachedReleaseSignature(string $sealPath,string $signaturePath,string $publicPath,?string $expectedPublicSha=null): array
{
    $errors=[];if(!extension_loaded('openssl'))return ['PHP OpenSSL extension is required for release signature verification'];foreach([$sealPath=>'release seal',$signaturePath=>'release signature',$publicPath=>'release public key'] as $path=>$label)if(!is_file($path))$errors[]="{$label} missing";if($errors!==[])return $errors;
    $publicPem=(string)file_get_contents($publicPath);$info=nexoraReleasePublicKeyInfo($publicPem);if(is_string($expectedPublicSha)&&!hash_equals(strtolower($expectedPublicSha),strtolower($info['public_sha256'])))$errors[]='release public key SHA-256 mismatch';$public=openssl_pkey_get_public($publicPem);if($public===false)$errors[]='unable to parse release public key';$sig=base64_decode(trim((string)file_get_contents($signaturePath)),true);if(!is_string($sig)||$sig==='')$errors[]='release signature is not valid base64';if($errors!==[])return $errors;$verified=openssl_verify((string)file_get_contents($sealPath),$sig,$public,OPENSSL_ALGO_SHA256);if($verified!==1)$errors[]='release seal signature verification failed';return $errors;
}
