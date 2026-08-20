<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/target-composer.php';

/** @return array{version:?string,path:?string,sha256:?string} */
function nexoraCertifiedToolExecutable(string $root,string $name,array $versionCommand): array
{
    $probe=nexoraRunTargetCommand($versionCommand,$root);$version=$probe['exit_code']===0?nexoraParseToolVersion($probe['stdout']!==''?$probe['stdout']:$probe['stderr']):null;
    $locator=PHP_OS_FAMILY==='Windows'?['where',$name]:['which',$name];$where=nexoraRunTargetCommand($locator,$root);$path=null;
    if($where['exit_code']===0){foreach(preg_split('/\R+/',trim($where['stdout']))?:[] as $candidate){$candidate=trim($candidate);if($candidate!==''&&is_file($candidate)){$path=$candidate;break;}}}
    return ['version'=>$version,'path'=>$path!==null?basename($path):null,'sha256'=>$path!==null?(hash_file('sha256',$path)?:null):null];
}

/** @return array<string,mixed> */
function nexoraCollectCertifiedToolchain(string $root): array
{
    $source=nexoraComputeSourceAttestation($root);$composer=nexoraLocateTargetComposer($root);$composerSha=null;$composerName=null;
    $composerPath=(string)($composer['path']??'');
    if($composerPath!==''&&$composerPath!=='composer'&&is_file($composerPath)){$composerSha=hash_file('sha256',$composerPath)?:null;$composerName=basename($composerPath);}else{
        $where=nexoraCertifiedToolExecutable($root,'composer',['composer','--version','--no-ansi']);$composerSha=$where['sha256'];$composerName=$where['path'];
    }
    $node=nexoraCertifiedToolExecutable($root,'node',['node','--version']);$npm=nexoraCertifiedToolExecutable($root,'npm',['npm','--version']);
    return [
        'schema'=>1,'status'=>'pass','platform_version'=>(string)((require $root.'/config/nexora.php')['version']??'unknown'),'source_tree_sha256'=>$source['tree_sha256'],'captured_at'=>gmdate(DATE_ATOM),
        'os_family'=>PHP_OS_FAMILY,'php'=>['version'=>PHP_VERSION,'sapi'=>PHP_SAPI,'binary'=>basename(PHP_BINARY),'binary_sha256'=>is_file(PHP_BINARY)?(hash_file('sha256',PHP_BINARY)?:null):null],
        'composer'=>['version'=>$composer['version']??null,'source'=>$composer['source']??null,'binary'=>$composerName,'binary_sha256'=>$composerSha],
        'node'=>$node,'npm'=>$npm,
        'locks'=>['composer_lock_sha256'=>is_file($root.'/composer.lock')?(hash_file('sha256',$root.'/composer.lock')?:null):null,'package_lock_sha256'=>is_file($root.'/package-lock.json')?(hash_file('sha256',$root.'/package-lock.json')?:null):null],
    ];
}

function nexoraCertifiedToolchainPath(string $root): string{return $root.'/storage/app/nexora/certification/toolchain.json';}

/** @return list<string> */
function nexoraValidateCertifiedToolchain(string $root,?array $data=null): array
{
    $errors=[];$path=nexoraCertifiedToolchainPath($root);
    if($data===null){if(!is_file($path))return ['certified toolchain evidence missing'];try{$data=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(Throwable $e){return ['invalid certified toolchain JSON: '.$e->getMessage()];}}
    if(!is_array($data))return ['certified toolchain must be an object'];$current=nexoraCollectCertifiedToolchain($root);
    foreach(['schema','status','platform_version','source_tree_sha256','os_family'] as $key)if(($data[$key]??null)!==($current[$key]??null))$errors[]="certified toolchain mismatch [{$key}]";
    foreach(['php','composer','node','npm'] as $tool){foreach(['version','binary_sha256'] as $key){$expected=$current[$tool][$key]??null;$recorded=$data[$tool][$key]??null;if(!is_string($expected)||$expected===''||$recorded!==$expected)$errors[]="certified toolchain mismatch [{$tool}.{$key}]";}}
    foreach(['composer_lock_sha256','package_lock_sha256'] as $key)if(($data['locks'][$key]??null)!==($current['locks'][$key]??null))$errors[]="certified toolchain lock mismatch [{$key}]";
    return $errors;
}

/** @return array<string,mixed> */
function nexoraWriteCertifiedToolchain(string $root): array
{
    $data=nexoraCollectCertifiedToolchain($root);$errors=[];
    foreach(['php','composer','node','npm'] as $tool)if(!is_string($data[$tool]['version']??null)||!is_string($data[$tool]['binary_sha256']??null))$errors[]="unable to fingerprint {$tool}";
    foreach(['composer_lock_sha256','package_lock_sha256'] as $key)if(!is_string($data['locks'][$key]??null))$errors[]="unable to fingerprint {$key}";
    if($errors!==[])throw new RuntimeException(implode('; ',$errors));$path=nexoraCertifiedToolchainPath($root);$dir=dirname($path);if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('Unable to create certification directory.');
    file_put_contents($path,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);return $data;
}
