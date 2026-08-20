<?php

declare(strict_types=1);

/**
 * Runtime/certification source roots that must remain immutable between
 * automated certification and production packaging. Generated dependencies,
 * Vite output and runtime evidence are intentionally excluded and are sealed
 * by their own lock/build/evidence hashes.
 *
 * @return list<string>
 */
function nexoraSourceAttestationRoots(): array
{
    return ['app','bootstrap','config','database','resources','routes','scripts','tests','themes','public'];
}

/** @return list<string> */
function nexoraSourceAttestationRootFiles(): array
{
    return [
        'artisan','composer.json','package.json','phpunit.xml','tsconfig.json','vite.config.ts',
        '.env.example','.env.production.example','SECURITY.md','ARCHITECTURE.md','CONTRIBUTING.md',
    ];
}

function nexoraSourceAttestationExcluded(string $relative): bool
{
    $relative=str_replace('\\','/',$relative);
    foreach([
        'bootstrap/cache/',
        'public/build/',
        'storage/',
        'dist/',
        'vendor/',
        'node_modules/',
    ] as $prefix) if(str_starts_with($relative,$prefix)) return true;
    return in_array($relative,['public/hot'],true);
}

/** @return array{schema:int,algorithm:string,file_count:int,tree_sha256:string,files:list<array{path:string,bytes:int,sha256:string}>} */
function nexoraComputeSourceAttestation(string $root): array
{
    $files=[];
    $add=static function(string $absolute,string $relative) use (&$files): void {
        if(!is_file($absolute)) return;
        $hash=hash_file('sha256',$absolute);
        if(!is_string($hash)||$hash==='') throw new RuntimeException("Unable to hash source file [{$relative}].");
        $files[]=['path'=>str_replace('\\','/',$relative),'bytes'=>(int)filesize($absolute),'sha256'=>$hash];
    };

    foreach(nexoraSourceAttestationRootFiles() as $relative){
        if(nexoraSourceAttestationExcluded($relative)) continue;
        $add($root.'/'.$relative,$relative);
    }
    foreach(nexoraSourceAttestationRoots() as $directory){
        $base=$root.'/'.$directory;
        if(!is_dir($base)) continue;
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($iterator as $file){
            if(!$file->isFile()) continue;
            $absolute=$file->getPathname();
            $relative=str_replace('\\','/',substr($absolute,strlen($root)+1));
            if(nexoraSourceAttestationExcluded($relative)) continue;
            $add($absolute,$relative);
        }
    }
    usort($files,static fn(array $a,array $b):int=>strcmp($a['path'],$b['path']));
    $context=hash_init('sha256');
    foreach($files as $file) hash_update($context,$file['path']."\0".$file['bytes']."\0".$file['sha256']."\n");
    return ['schema'=>1,'algorithm'=>'sha256-path-size-content-v1','file_count'=>count($files),'tree_sha256'=>hash_final($context),'files'=>$files];
}

function nexoraWriteSourceAttestation(string $root,string $path): array
{
    $payload=nexoraComputeSourceAttestation($root);
    $payload['platform_version']=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $payload['created_at']=gmdate(DATE_ATOM);
    $dir=dirname($path);
    if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)) throw new RuntimeException('Unable to create source-attestation directory.');
    file_put_contents($path,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
    return $payload;
}
