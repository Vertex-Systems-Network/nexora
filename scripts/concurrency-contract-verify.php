<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/concurrency-contracts.php';
$result=nexoraAnalyzeConcurrencyContracts($root);
if($result['ok']){
    $m=$result['metrics'];
    fwrite(STDOUT,"[Nexora Concurrency Contracts] PASS — {$m['critical_surfaces']} critical surfaces; {$m['portable_mutexes']} portable mutex; {$m['claim_ttls']} stale-claim TTLs; {$m['critical_direct_transactions']} direct critical transactions.\n");
    exit(0);
}
fwrite(STDERR,"[Nexora Concurrency Contracts] FAIL\n");
foreach($result['errors'] as $error)fwrite(STDERR," - {$error}\n");
foreach($result['warnings'] as $warning)fwrite(STDERR," ! {$warning}\n");
exit(1);
