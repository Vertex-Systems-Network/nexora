<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-resume-contracts.php';
$result=nexoraAnalyzeTargetResumeContracts($root);
if($result['errors']!==[]){ fwrite(STDERR,"[Nexora Target Resume Contracts] FAIL\n - ".implode("\n - ",$result['errors'])."\n"); exit(1); }
fwrite(STDOUT,"[Nexora Target Resume Contracts] PASS — {$result['metrics']['bootstrap_wrappers']} bootstrap wrappers; {$result['metrics']['resume_fingerprints']} resume fingerprints; {$result['metrics']['evidence_bindings']} evidence bindings.\n");
