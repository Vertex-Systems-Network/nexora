<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/dependency-lock-intake.php';
$platform=require $root.'/config/nexora.php';
foreach(['composer.lock','package-lock.json'] as $lock){if(!is_file($root.'/'.$lock)){fwrite(STDERR,"[Nexora Dependency Provenance] Missing {$lock}.\n");exit(1);}}
try{
    $composer=json_decode((string)file_get_contents($root.'/composer.lock'),true,512,JSON_THROW_ON_ERROR);
    $npm=json_decode((string)file_get_contents($root.'/package-lock.json'),true,512,JSON_THROW_ON_ERROR);
}catch(Throwable $e){fwrite(STDERR,"[Nexora Dependency Provenance] Invalid lockfile JSON: {$e->getMessage()}\n");exit(1);}
$composerPackages=[];
foreach(array_merge((array)($composer['packages']??[]),(array)($composer['packages-dev']??[])) as $pkg){
    if(!is_array($pkg)||!isset($pkg['name'],$pkg['version']))continue;
    $composerPackages[]=[
        'name'=>(string)$pkg['name'],'version'=>(string)$pkg['version'],
        'dist_type'=>$pkg['dist']['type']??null,'dist_reference'=>$pkg['dist']['reference']??null,
        'source_reference'=>$pkg['source']['reference']??null,
    ];
}
$npmPackages=[];
$integritySummary=nexoraNpmLockIntegritySummary($npm);
if($integritySummary['missing']!==[]){
    fwrite(STDERR,"[Nexora Dependency Provenance] npm packages missing verified integrity coverage: ".implode(', ',array_slice($integritySummary['missing'],0,20))."\n");
    exit(1);
}
$npmPackageMap=(array)($npm['packages']??[]);
foreach($npmPackageMap as $path=>$pkg){
    if($path===''||!is_array($pkg)||!isset($pkg['version']))continue;
    $name=(string)($pkg['name']??preg_replace('#^node_modules/#','',(string)$path));
    $isLink=(bool)($pkg['link']??false);
    $integrity=$pkg['integrity']??null;
    $coverage=$isLink?['mode'=>'link','owner_path'=>null]:nexoraNpmPackageIntegrityCoverage($npmPackageMap,(string)$path,$pkg);
    $npmPackages[]=[
        'name'=>$name,
        'version'=>(string)$pkg['version'],
        'integrity'=>$integrity,
        'integrity_mode'=>$coverage['mode']??'unknown',
        'bundle_owner_path'=>$coverage['owner_path']??null,
        'link'=>$isLink,
    ];
}
usort($composerPackages,static fn($a,$b)=>strcmp($a['name'],$b['name']));
usort($npmPackages,static fn($a,$b)=>strcmp($a['name'],$b['name']));
$payload=[
    'schema'=>1,'status'=>'pass','platform_version'=>(string)$platform['version'],'generated_at'=>gmdate(DATE_ATOM),
    'composer_lock_sha256'=>hash_file('sha256',$root.'/composer.lock'),'package_lock_sha256'=>hash_file('sha256',$root.'/package-lock.json'),
    'counts'=>['composer'=>count($composerPackages),'npm'=>count($npmPackages),'npm_bundled_integrity_covered'=>count($integritySummary['bundled_covered'])],
    'composer'=>$composerPackages,'npm'=>$npmPackages,
];
$dir=$root.'/storage/app/nexora/certification';if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)){fwrite(STDERR,"Unable to create certification directory.\n");exit(1);}
file_put_contents($dir.'/dependency-provenance.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
fwrite(STDOUT,"[Nexora Dependency Provenance] PASS — ".count($composerPackages)." Composer packages; ".count($npmPackages)." npm packages.\n");
