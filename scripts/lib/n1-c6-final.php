<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';require_once __DIR__.'/n1-certification-session.php';

/** @return array<string,string> */
function nexoraN10C6ChunkEvidenceFiles(string $root): array
{
    return [
        'c1'=>$root.'/storage/app/nexora/n1-c1/latest.json',
        'c2'=>$root.'/storage/app/nexora/n1-c2/latest.json',
        'c3'=>$root.'/storage/app/nexora/certification/database-matrix.json',
        'c4'=>$root.'/storage/app/nexora/n1-c4/c4-evidence.json',
        'c5'=>$root.'/storage/app/nexora/n1-c5/c5-evidence.json',
    ];
}

/** @return list<string> */
function nexoraValidateN10C6ChunkEvidence(string $root): array
{
    $errors=[];$version=(string)((require $root.'/config/nexora.php')['version']??'unknown');$source=nexoraComputeSourceAttestation($root);
    $expected=['c1'=>'N1.0-C1','c2'=>'N1.0-C2','c3'=>'N1.0-C3','c4'=>'N1.0-C4','c5'=>'N1.0-C5'];
    foreach(nexoraN10C6ChunkEvidenceFiles($root) as $key=>$path){
        $data=nexoraEvidenceJson($path);
        if($data===null){$errors[]="{$expected[$key]} PASS evidence missing/invalid";continue;}
        if(($data['status']??null)!=='pass')$errors[]="{$expected[$key]} status must be pass";
        if(($data['chunk']??null)!==$expected[$key])$errors[]="{$expected[$key]} chunk identity mismatch";
        if(($data['platform_version']??null)!==$version)$errors[]="{$expected[$key]} platform_version mismatch";
        if(($data['source_tree_sha256']??null)!==$source['tree_sha256'])$errors[]="{$expected[$key]} source-tree digest mismatch";
        if($key==='c3')foreach(nexoraValidateDatabaseMatrixEvidence($root,$data) as $error)$errors[]='N1.0-C3: '.$error;
        if(in_array($key,['c4','c5'],true)){foreach(nexoraValidateEvidenceSessionBinding($root,$data,$expected[$key].' chunk evidence') as $error)$errors[]=$error;}
    }
    return $errors;
}

/** @return array{path:string,file_version:string} */
function nexoraN10C6ProductionArtifactPath(string $root): array
{
    $version=(string)((require $root.'/config/nexora.php')['version']??'unknown');
    $fileVersion=preg_replace('/[^0-9A-Za-z._-]+/','-',$version)?:'unknown';
    return ['path'=>$root.'/dist/nexora-'.$fileVersion.'-production.zip','file_version'=>$fileVersion];
}
