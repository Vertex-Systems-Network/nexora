<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-evidence.php';
$path=(string)(getenv('NEXORA_HA_EVIDENCE') ?: $root.'/storage/app/nexora/certification/ha-evidence.json');
$data=nexoraEvidenceJson($path);
if($data===null){fwrite(STDERR,"[Nexora HA Evidence] Missing or invalid evidence: {$path}\nCopy docs/ha-certification-evidence.example.json and record a real multi-node rehearsal.\n");exit(1);}
$errors=nexoraValidateHaEvidence($root,$data);
if($errors!==[]){fwrite(STDERR,"[Nexora HA Evidence] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);}
fwrite(STDOUT,"[Nexora HA Evidence] PASS — independent nodes + shared state + scheduler/queue/drain/failover observations passed.\n");
