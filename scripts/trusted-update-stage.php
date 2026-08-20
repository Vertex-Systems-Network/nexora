<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/trusted-update.php';
require_once $root.'/scripts/lib/source-attestation.php';

$production=null;$destination=null;
foreach(array_slice($argv,1) as $a){if(str_starts_with($a,'--production='))$production=substr($a,13);elseif(str_starts_with($a,'--destination='))$destination=substr($a,14);}
if(!$production||!$destination){fwrite(STDERR,"Usage: php scripts/trusted-update-stage.php --production=<zip> --destination=<empty-dir>\n");exit(2);}
if(!class_exists(ZipArchive::class)){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — PHP ext-zip required.\n");exit(1);}
$receipt=nexoraUpdateTrustReadJson(nexoraUpdateAdmissionPath($root));
if(!is_array($receipt)||($receipt['status']??null)!=='admitted'){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — valid update admission receipt missing.\n");exit(1);}
if(!is_file($production)||($receipt['production_sha256']??null)!==(hash_file('sha256',$production)?:null)){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — production archive does not match admission receipt.\n");exit(1);}
foreach(nexoraValidateZipArchiveHygiene($root,$production) as $e){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — {$e}\n");exit(1);}
if(file_exists($destination)&&(!is_dir($destination)||count(scandir($destination)?:[])>2)){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — destination must be absent or empty.\n");exit(1);}
if(!is_dir($destination)&&!mkdir($destination,0755,true)&&!is_dir($destination)){fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — unable to create destination.\n");exit(1);}
$receiptSha=hash_file('sha256',nexoraUpdateAdmissionPath($root))?:null;
try{
    nexoraPublishUpdateStageRecord($root,$destination,['status'=>'extracting','admission_sha256'=>$receiptSha,'production_sha256'=>$receipt['production_sha256']??null,'started_at'=>gmdate(DATE_ATOM)]);
    $z=new ZipArchive();$open=$z->open($production,ZipArchive::RDONLY);if($open!==true)throw new RuntimeException('archive open failed');
    try{if(!$z->extractTo($destination))throw new RuntimeException('archive extraction failed');}finally{$z->close();}
    $att=nexoraComputeSourceAttestation(rtrim($destination,'/\\'));
    if(($receipt['target_source_tree_sha256']??null)!==$att['tree_sha256'])throw new RuntimeException('extracted source digest does not match admitted signed release');
    nexoraPublishUpdateStageRecord($root,$destination,['status'=>'verified','admission_sha256'=>$receiptSha,'production_sha256'=>$receipt['production_sha256']??null,'source_tree_sha256'=>$att['tree_sha256'],'verified_at'=>gmdate(DATE_ATOM)]);
    fwrite(STDOUT,"[Nexora Trusted Update Stage] PASS — verified production source staged at {$destination}.\nSource SHA-256: {$att['tree_sha256']}\n");
}catch(Throwable $e){
    try{nexoraPublishUpdateStageRecord($root,$destination,['status'=>'quarantined','admission_sha256'=>$receiptSha,'production_sha256'=>$receipt['production_sha256']??null,'quarantined_at'=>gmdate(DATE_ATOM),'reason'=>substr($e->getMessage(),0,1000)]);}catch(Throwable){}
    fwrite(STDERR,"[Nexora Trusted Update Stage] FAIL — {$e->getMessage()}. Partial staging data is quarantined by record; cleanup is explicit.\n");exit(1);
}
