<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/target-runtime-contracts.php';
$result=nexoraAnalyzeTargetRuntimeContracts($root);
if($result['errors']!==[]){
    fwrite(STDERR,"[Nexora Target Runtime Contracts] FAIL\n - ".implode("\n - ",$result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT,"[Nexora Target Runtime Contracts] PASS — {$result['metrics']['wrappers']} wrappers; fail-fast target gate delegates destructive work to isolated certification.\n");
