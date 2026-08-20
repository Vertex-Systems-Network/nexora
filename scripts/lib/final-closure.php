<?php

declare(strict_types=1);

require_once __DIR__.'/final-evidence.php';
require_once __DIR__.'/release-artifact.php';
require_once __DIR__.'/final-release-seal.php';

/** @return array{status:string,detail:string,sha256:?string} */
function nexoraClosureJsonDomain(string $path, string $version, string $expectedStatus='pass'): array
{
    $data=nexoraEvidenceJson($path);
    if($data===null) return ['status'=>'pending','detail'=>'missing or invalid '.basename($path),'sha256'=>null];
    if(($data['platform_version']??null)!==$version) return ['status'=>'fail','detail'=>'platform_version mismatch in '.basename($path),'sha256'=>hash_file('sha256',$path)?:null];
    if(($data['status']??null)!==$expectedStatus) return ['status'=>'fail','detail'=>'status is '.(string)($data['status']??'missing').' in '.basename($path),'sha256'=>hash_file('sha256',$path)?:null];
    return ['status'=>'pass','detail'=>basename($path).' is '.$expectedStatus,'sha256'=>hash_file('sha256',$path)?:null];
}

/** @return array<string,mixed> */
function nexoraEvaluateFinalClosure(string $root): array
{
    $platform=require $root.'/config/nexora.php';
    $version=(string)($platform['version']??'unknown');
    $dir=$root.'/storage/app/nexora/certification';
    $domains=[];

    $domains['automated_certification']=nexoraClosureJsonDomain($dir.'/latest.json',$version,'certification-pass');
    $domains['build_assets']=nexoraClosureJsonDomain($dir.'/build-assets.json',$version,'pass');
    $domains['http_performance']=nexoraClosureJsonDomain($dir.'/http-performance.json',$version,'pass');
    foreach(['automated_certification'=>'latest.json','build_assets'=>'build-assets.json','http_performance'=>'http-performance.json'] as $domain=>$file){$data=nexoraEvidenceJson($dir.'/'.$file);if($data!==null && isset($data['source_tree_sha256']) && !hash_equals(nexoraCurrentSourceTreeSha256($root),(string)$data['source_tree_sha256']))$domains[$domain]=['status'=>'fail','detail'=>'source-tree digest mismatch in '.$file,'sha256'=>hash_file('sha256',$dir.'/'.$file)?:null];}
    $matrix=nexoraEvidenceJson($dir.'/database-matrix.json');
    if($matrix===null)$domains['database_matrix']=['status'=>'pending','detail'=>'strict five-driver database matrix evidence missing','sha256'=>null];else{$errors=nexoraValidateDatabaseMatrixEvidence($root,$matrix);$domains['database_matrix']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'strict five-driver database matrix passes':implode('; ',$errors),'sha256'=>hash_file('sha256',$dir.'/database-matrix.json')?:null];}

    $zeroPath=(string)(getenv('NEXORA_ZERO_INSTALL_EVIDENCE') ?: $dir.'/zero-install-evidence.json');$zero=nexoraEvidenceJson($zeroPath);if($zero===null)$domains['zero_install']=['status'=>'pending','detail'=>'observed zero-install/recovery evidence missing','sha256'=>null];else{$errors=nexoraValidateZeroInstallEvidence($root,$zero);$domains['zero_install']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'fresh browser install + interrupted recovery evidence passes':implode('; ',$errors),'sha256'=>hash_file('sha256',$zeroPath)?:null];}
    $upgradePath=(string)(getenv('NEXORA_UPGRADE_REHEARSAL_EVIDENCE') ?: $dir.'/upgrade-rehearsal-evidence.json');$upgrade=nexoraEvidenceJson($upgradePath);if($upgrade===null)$domains['upgrade_rehearsal']=['status'=>'pending','detail'=>'existing-install upgrade rehearsal evidence missing','sha256'=>null];else{$errors=nexoraValidateUpgradeRehearsalEvidence($root,$upgrade);$domains['upgrade_rehearsal']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'existing-install upgrade rehearsal passes':implode('; ',$errors),'sha256'=>hash_file('sha256',$upgradePath)?:null];}

    $browserPath=(string)(getenv('NEXORA_BROWSER_EVIDENCE') ?: $dir.'/browser-evidence.json');
    $browser=nexoraEvidenceJson($browserPath);
    if($browser===null){
        $domains['browser']=['status'=>'pending','detail'=>'browser evidence missing','sha256'=>null];
    } else {
        $errors=nexoraValidateBrowserEvidenceForFinal($root,$browser);
        $domains['browser']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'browser matrix/operator checks pass':implode('; ',$errors),'sha256'=>hash_file('sha256',$browserPath)?:null];
    }

    $backupPath=(string)(getenv('NEXORA_BACKUP_RESTORE_EVIDENCE') ?: $dir.'/backup-restore-evidence.json');
    $backup=nexoraEvidenceJson($backupPath);
    if($backup===null){
        $domains['backup_restore']=['status'=>'pending','detail'=>'backup/restore evidence missing','sha256'=>null];
    } else {
        $errors=nexoraValidateBackupRestoreEvidence($root,$backup);
        $domains['backup_restore']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'disposable-target restore evidence passes':implode('; ',$errors),'sha256'=>hash_file('sha256',$backupPath)?:null];
    }

    $haPath=(string)(getenv('NEXORA_HA_EVIDENCE') ?: $dir.'/ha-evidence.json');
    $ha=nexoraEvidenceJson($haPath);
    if($ha===null){
        $domains['multi_node_ha']=['status'=>'pending','detail'=>'multi-node HA evidence missing','sha256'=>null];
    } else {
        $errors=nexoraValidateHaEvidence($root,$ha);
        $domains['multi_node_ha']=['status'=>$errors===[]?'pass':'fail','detail'=>$errors===[]?'independent-node HA evidence passes':implode('; ',$errors),'sha256'=>hash_file('sha256',$haPath)?:null];
    }

    $domains['final_evidence']=nexoraClosureJsonDomain($dir.'/final-evidence.json',$version,'pass');

    $fileVersion=preg_replace('/[^0-9A-Za-z._-]+/','-',$version) ?: 'unknown';
    $production=$root.'/dist/nexora-'.$fileVersion.'-production.zip';
    $artifact=nexoraValidateProductionArtifact($root,$production);$finalizationErrors=nexoraValidateCertificationSessionFinalization($root);
    $packageOk=$artifact['ok']&&$finalizationErrors===[];
    $packageErrors=array_merge($artifact['errors'],$finalizationErrors);
    $domains['production_package']=!is_file($production)
        ? ['status'=>'pending','detail'=>'certified signed production ZIP not generated yet','sha256'=>null]
        : ['status'=>$packageOk?'pass':'fail','detail'=>$packageOk?'signed production ZIP + certification evidence bundle + detached signature + release seal + finalized session independently revalidated':implode('; ',$packageErrors),'sha256'=>$artifact['sha256']];

    $blocking=[];
    foreach($domains as $name=>$domain){
        if($name==='production_package') continue; // readiness precedes packaging
        if(($domain['status']??null)!=='pass') $blocking[]=$name;
    }
    $status=$blocking===[]?'ready-for-production-package':'blocked';
    return [
        'schema'=>2,
        'status'=>$status,
        'platform_version'=>$version,
        'checked_at'=>gmdate(DATE_ATOM),
        'domains'=>$domains,
        'blocking_domains'=>$blocking,
        'n1_0_done'=>$blocking===[] && ($domains['production_package']['status']??null)==='pass',
    ];
}

function nexoraWriteClosureStatus(string $root, array $payload): void
{
    $dir=$root.'/storage/app/nexora/certification';
    if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) throw new RuntimeException('Unable to create certification directory.');
    file_put_contents($dir.'/closure-status.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);
    $md="# Nexora N1.0 closure status\n\n";
    $md.="Platform: `{$payload['platform_version']}`  \nStatus: **{$payload['status']}**  \nChecked: {$payload['checked_at']}\n\n";
    $md.="| Domain | Status | Detail |\n|---|---:|---|\n";
    foreach($payload['domains'] as $name=>$domain){
        $detail=str_replace('|','\\|',(string)($domain['detail']??''));
        $md.='| '.str_replace('_',' ',$name).' | '.strtoupper((string)($domain['status']??'unknown')).' | '.$detail." |\n";
    }
    if($payload['blocking_domains']!==[]) $md.="\nBlocking: `".implode('`, `',$payload['blocking_domains'])."`\n";
    $md.="\nN1.0 DONE: **".(($payload['n1_0_done']??false)?'YES':'NO')."**\n";
    file_put_contents($dir.'/closure-status.md',$md);
}
