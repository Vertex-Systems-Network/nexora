<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
$errors=[];
foreach(['scripts/lib/source-attestation.php','scripts/source-attestation.php'] as $file) if(!is_file($root.'/'.$file)) $errors[]="missing {$file}";
$payload=nexoraComputeSourceAttestation($root);
if(($payload['file_count']??0)<500)$errors[]='source attestation unexpectedly covers fewer than 500 files';
if(preg_match('/^[a-f0-9]{64}$/',(string)($payload['tree_sha256']??''))!==1)$errors[]='source tree digest is not SHA-256';
$runner=(string)@file_get_contents($root.'/scripts/certify-release.php');
$builder=(string)@file_get_contents($root.'/scripts/build-production-release.php');
foreach(['source-attestation-contract','source_tree_sha256','nexoraComputeSourceAttestation'] as $marker) if(!str_contains($runner,$marker)) $errors[]="certification runner missing source-attestation marker [{$marker}]";
foreach(['source_tree_sha256','nexoraComputeSourceAttestation'] as $marker) if(!str_contains($builder,$marker)) $errors[]="production builder missing source-attestation marker [{$marker}]";
if($errors!==[]){fwrite(STDERR,"[Nexora Source Attestation Contracts] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);} 
fwrite(STDOUT,"[Nexora Source Attestation Contracts] PASS — {$payload['file_count']} files; current digest {$payload['tree_sha256']}\n");
