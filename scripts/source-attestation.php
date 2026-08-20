<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
$target=$root.'/storage/app/nexora/certification/source-attestation.json';
$write=in_array('--write',$argv,true);
$expected=null;
foreach($argv as $arg) if(str_starts_with($arg,'--expect=')) $expected=strtolower(trim(substr($arg,9)));
$payload=$write?nexoraWriteSourceAttestation($root,$target):nexoraComputeSourceAttestation($root);
if($expected!==null && !hash_equals($expected,strtolower((string)$payload['tree_sha256']))){
    fwrite(STDERR,"[Nexora Source Attestation] FAIL — source tree digest changed.\nExpected: {$expected}\nActual:   {$payload['tree_sha256']}\n");
    exit(1);
}
fwrite(STDOUT,"[Nexora Source Attestation] PASS — {$payload['file_count']} files; SHA-256 {$payload['tree_sha256']}\n");
if($write) fwrite(STDOUT,"Evidence: {$target}\n");
