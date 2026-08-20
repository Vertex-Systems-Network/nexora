<?php

declare(strict_types=1);

/** @param array<string,mixed> $materials */
function nexoraDeploymentGeneration(array $materials): string
{
    $keys=['platform_version','source_tree_sha256','frontend_manifest_sha256','composer_lock_sha256','package_lock_sha256','runtime_policy_sha256','upgrade_policy_sha256','activation_policy_sha256','engine_policy_sha256','database_policy_sha256','storage_policy_sha256','network_policy_sha256','host_policy_sha256','resource_policy_sha256','policy_plane_sha256','process_policy_sha256','framework_policy_sha256','session_schema'];
    $canonical=[];
    foreach($keys as $key){$value=$materials[$key]??null;$canonical[$key]=is_string($value)?strtolower(trim($value)):$value;}
    return hash('sha256',json_encode($canonical,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
}

/** @return array<string,mixed> */
function nexoraDeploymentMaterialsFromRoot(string $root,?string $sourceTreeSha256=null): array
{
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $hash=static fn(string $path):?string=>is_file($path)?(hash_file('sha256',$path)?:null):null;
    return [
        'platform_version'=>$version,
        'source_tree_sha256'=>$sourceTreeSha256,
        'frontend_manifest_sha256'=>$hash($root.'/public/build/manifest.json'),
        'composer_lock_sha256'=>$hash($root.'/composer.lock'),
        'package_lock_sha256'=>$hash($root.'/package-lock.json'),
        'runtime_policy_sha256'=>$hash($root.'/config/nexora-runtime.php'),
        'upgrade_policy_sha256'=>$hash($root.'/config/nexora-upgrade.php'),
        'activation_policy_sha256'=>$hash($root.'/config/nexora-activation.php'),
        'engine_policy_sha256'=>$hash($root.'/config/nexora-engine.php'),
        'database_policy_sha256'=>$hash($root.'/config/nexora-database-runtime.php'),
        'storage_policy_sha256'=>$hash($root.'/config/nexora-storage-runtime.php'),
        'network_policy_sha256'=>$hash($root.'/config/nexora-network-runtime.php'),
        'host_policy_sha256'=>$hash($root.'/config/nexora-host-runtime.php'),
        'resource_policy_sha256'=>$hash($root.'/config/nexora-resource-runtime.php'),
        'policy_plane_sha256'=>$hash($root.'/config/nexora-policy-runtime.php'),
        'process_policy_sha256'=>$hash($root.'/config/nexora-process-runtime.php'),
        'framework_policy_sha256'=>$hash($root.'/config/nexora-framework.php'),
        'session_schema'=>max(1,(int)(getenv('NEXORA_SESSION_SCHEMA')?:1)),
    ];
}
