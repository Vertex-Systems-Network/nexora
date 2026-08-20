<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-diagnostics-contracts.php';
$result=nexoraAnalyzeTargetDiagnosticsContracts($root);
if($result['errors']!==[]){
    fwrite(STDERR,"[Nexora Target Diagnostics Contracts] FAILED\n - ".implode("\n - ",$result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT,"[Nexora Target Diagnostics Contracts] PASS — {$result['metrics']['diagnostic_groups']} groups; {$result['metrics']['runner_modes']} execution modes.\n");
