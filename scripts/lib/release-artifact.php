<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/deployment-generation.php';
require_once __DIR__.'/release-archive-hygiene.php';
require_once __DIR__.'/release-content-manifest.php';
require_once __DIR__.'/production-dependency-stage.php';

/** @return array{ok:bool,errors:list<string>,sha256:?string,manifest:?array} */
function nexoraValidateProductionArtifactCore(string $root,string $path): array
{
    $errors=[];$manifest=null;$hash=null;
    if(!is_file($path)) return ['ok'=>false,'errors'=>['production ZIP missing'],'sha256'=>null,'manifest'=>null];
    if(!class_exists(ZipArchive::class)) return ['ok'=>false,'errors'=>['PHP ext-zip required to verify production artifact'],'sha256'=>null,'manifest'=>null];
    foreach(nexoraValidateZipArchiveHygiene($root,$path) as $error)$errors[]='archive hygiene: '.$error;
    $policy=require $root.'/config/nexora-release.php';
    $zip=new ZipArchive();if($zip->open($path,ZipArchive::RDONLY)!==true)return ['ok'=>false,'errors'=>['production ZIP cannot be opened'],'sha256'=>null,'manifest'=>null];
    foreach((array)($policy['required_archive_entries']??[]) as $entry) if($zip->locateName((string)$entry)===false)$errors[]="missing required archive entry [{$entry}]";
    for($i=0;$i<$zip->numFiles;$i++){
        $entry=(string)$zip->getNameIndex($i);
        if(in_array($entry,(array)($policy['forbidden_archive_entries']??[]),true))$errors[]="forbidden archive entry [{$entry}]";
        foreach((array)($policy['forbidden_archive_prefixes']??[]) as $prefix) if(str_starts_with($entry,(string)$prefix))$errors[]="forbidden archive prefix [{$prefix}] via [{$entry}]";
    }
    foreach(nexoraValidateReleaseContentManifest($zip) as $e)$errors[]='content manifest: '.$e;
    $raw=$zip->getFromName('nexora-release.json');
    if(!is_string($raw))$errors[]='nexora-release.json missing';else{try{$manifest=json_decode($raw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$errors[]='invalid nexora-release.json: '.$e->getMessage();}}
    if(is_array($manifest)){
        $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
        if(($manifest['schema']??null)!==4)$errors[]='release manifest schema mismatch';
        if(($manifest['version']??null)!==$version)$errors[]='release manifest version mismatch';
        $runtimeDeployment=is_array($manifest['runtime_deployment']??null)?$manifest['runtime_deployment']:null;if(!is_array($runtimeDeployment))$errors[]='release runtime deployment identity missing';else{$materials=is_array($runtimeDeployment['materials']??null)?$runtimeDeployment['materials']:[];$computed=nexoraDeploymentGeneration($materials);$declared=strtolower(trim((string)($runtimeDeployment['generation']??'')));if(preg_match('/^[a-f0-9]{64}$/',$declared)!==1||!hash_equals($declared,$computed))$errors[]='release runtime deployment generation mismatch';if(($materials['platform_version']??null)!==$version)$errors[]='release runtime deployment platform version mismatch';if(($materials['source_tree_sha256']??null)!==($manifest['certification']['source_tree_sha256']??null))$errors[]='release runtime deployment source digest mismatch';if(($materials['frontend_manifest_sha256']??null)!==($manifest['artifacts']['frontend_manifest_sha256']??null))$errors[]='release runtime deployment frontend manifest mismatch';}
        $source=nexoraComputeSourceAttestation($root);
        if(($manifest['certification']['source_tree_sha256']??null)!==$source['tree_sha256'])$errors[]='release manifest source_tree_sha256 does not match current certified source';
        foreach(['composer.lock'=>'composer_lock_sha256','package-lock.json'=>'package_lock_sha256','public/build/manifest.json'=>'frontend_manifest_sha256'] as $entry=>$key){
            $contents=$zip->getFromName($entry);if(!is_string($contents))continue;$actual=hash('sha256',$contents);if(($manifest['artifacts'][$key]??null)!==$actual)$errors[]="release artifact hash mismatch for [{$entry}]";
        }
        foreach(['config/nexora-release.php'=>'release_policy_sha256','config/nexora-certification-evidence.php'=>'certification_evidence_policy_sha256','config/nexora-release-trust.php'=>'release_trust_policy_sha256','config/nexora-supply-chain.php'=>'supply_chain_policy_sha256','config/nexora-update-trust.php'=>'update_trust_policy_sha256'] as $entry=>$key){$contents=$zip->getFromName($entry);if(!is_string($contents)){$errors[]="release policy artifact missing [{$entry}]";continue;}$actual=hash('sha256',$contents);if(($manifest[$key]??null)!==$actual)$errors[]="release policy hash mismatch for [{$entry}]";}
        $sbom=$zip->getFromName('nexora-sbom.json');$prov=$zip->getFromName('nexora-provenance.json');
        if(!is_string($sbom)||hash('sha256',$sbom)!==($manifest['artifacts']['dependency_sbom_sha256']??null))$errors[]='release SBOM hash mismatch';
        if(!is_string($prov)||hash('sha256',$prov)!==($manifest['artifacts']['release_provenance_sha256']??null))$errors[]='release provenance hash mismatch';
        if(($manifest['signing']['trust_anchor_sha256']??null)!==(is_file($root.'/storage/app/nexora/release-signing/trust-anchor.json')?hash_file('sha256',$root.'/storage/app/nexora/release-signing/trust-anchor.json'):null))$errors[]='release manifest trust-anchor hash mismatch';
        // Prove the ZIP contains only Composer production dependencies.
        $lockRaw=$zip->getFromName('composer.lock');$installedRaw=$zip->getFromName('vendor/composer/installed.json');
        if(!is_string($lockRaw)||!is_string($installedRaw))$errors[]='production Composer lock/installed metadata missing';else{
            try{$lock=json_decode($lockRaw,true,512,JSON_THROW_ON_ERROR);$installed=json_decode($installedRaw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){$lock=$installed=null;$errors[]='invalid Composer metadata inside production artifact: '.$e->getMessage();}
            if(is_array($lock)&&is_array($installed)){
                $prod=[];foreach((array)($lock['packages']??[]) as $p)if(is_array($p)&&isset($p['name']))$prod[(string)$p['name']]=true;
                $dev=[];foreach((array)($lock['packages-dev']??[]) as $p)if(is_array($p)&&isset($p['name'])&&!isset($prod[(string)$p['name']]))$dev[(string)$p['name']]=true;
                $rows=is_array($installed['packages']??null)?$installed['packages']:$installed;$installedNames=[];
                foreach((array)$rows as $p)if(is_array($p)&&isset($p['name'])){$name=(string)$p['name'];$installedNames[$name]=true;if(isset($dev[$name]))$errors[]="dev-only Composer package present in production artifact [{$name}]";}
                foreach(array_keys($prod) as $name)if(!isset($installedNames[$name]))$errors[]="required Composer production package missing [{$name}]";
            }
        }
        // Recompute the staged vendor tree fingerprint from the ZIP content manifest.
        $cmRaw=$zip->getFromName('nexora-content-manifest.json');if(is_string($cmRaw)){try{$cm=json_decode($cmRaw,true,1024,JSON_THROW_ON_ERROR);}catch(Throwable){$cm=null;}if(is_array($cm)){$rows=[];foreach((array)($cm['entries']??[]) as $name=>$r){if(!str_starts_with((string)$name,'vendor/')||!is_array($r))continue;$rel=substr((string)$name,7);$rows[]=$rel."\0".(string)($r['sha256']??'')."\0".(int)($r['size']??0);}sort($rows,SORT_STRING);$tree=hash('sha256',implode("\n",$rows));if(($manifest['artifacts']['production_vendor_tree_sha256']??null)!==$tree)$errors[]='production vendor tree fingerprint mismatch';}}
        foreach(['storage/app/nexora/certification/latest.json'=>'report_sha256','storage/app/nexora/certification/build-assets.json'=>'performance_report_sha256','storage/app/nexora/certification/final-evidence.json'=>'final_evidence_report_sha256','storage/app/nexora/certification/dependency-audit.json'=>'dependency_audit_report_sha256','storage/app/nexora/certification/dependency-provenance.json'=>'dependency_provenance_report_sha256','storage/app/nexora/certification/dependency-sbom.json'=>'dependency_sbom_sha256','storage/app/nexora/certification/production-dependencies.json'=>'production_dependencies_sha256','storage/app/nexora/certification/release-provenance.json'=>'release_provenance_sha256'] as $hostFile=>$key){$host=$root.'/'.$hostFile;if(is_file($host)){ $actual=hash_file('sha256',$host)?:null; if(($manifest['certification'][$key]??null)!==$actual && ($manifest['artifacts'][$key]??null)!==$actual)$errors[]="release manifest current-host report hash mismatch [{$key}]"; }}
        $toolchain=$root.'/storage/app/nexora/certification/toolchain.json';if(is_file($toolchain)){ $actual=hash_file('sha256',$toolchain)?:null; if(($manifest['certification']['certified_toolchain_sha256']??null)!==$actual)$errors[]='release manifest current-host certified toolchain hash mismatch'; }
    }
    $zip->close();$hash=hash_file('sha256',$path)?:null;
    $sidecar=$path.'.sha256';if(!is_file($sidecar))$errors[]='production SHA-256 sidecar missing';else{$declared=strtolower(trim((string)preg_split('/\s+/',trim((string)file_get_contents($sidecar)))[0]));if($hash===null||!hash_equals($hash,$declared))$errors[]='production SHA-256 sidecar mismatch';}
    return ['ok'=>$errors===[],'errors'=>array_values(array_unique($errors)),'sha256'=>$hash,'manifest'=>$manifest];
}

/** @return array{ok:bool,errors:list<string>,sha256:?string,manifest:?array} */
function nexoraValidateProductionArtifact(string $root,string $path): array
{
    $core=nexoraValidateProductionArtifactCore($root,$path);if(!$core['ok'])return $core;
    require_once __DIR__.'/final-release-seal.php';$sealed=nexoraValidateFinalReleaseSeal($root,$path);
    if(!$sealed['ok'])return ['ok'=>false,'errors'=>array_merge($core['errors'],$sealed['errors']),'sha256'=>$core['sha256'],'manifest'=>$core['manifest']];return $core;
}
