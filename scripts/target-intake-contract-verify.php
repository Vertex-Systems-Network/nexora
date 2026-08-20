<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-intake-contracts.php';
$result=nexoraAnalyzeTargetIntakeContracts($root);
if($result['errors']!==[]){fwrite(STDERR,"[Nexora Target Intake Contracts] FAIL\n - ".implode("\n - ",$result['errors'])."\n");exit(1);}
fwrite(STDOUT,"[Nexora Target Intake Contracts] PASS — {$result['metrics']['intake_wrappers']} intake wrappers; {$result['metrics']['lock_review_wrappers']} lock-review wrappers; {$result['metrics']['attestation_hash_bindings']} hash bindings.\n");
