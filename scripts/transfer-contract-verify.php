<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require_once $root.'/scripts/lib/transfer-contracts.php';
$result=nexoraAnalyzeTransferContracts($root);
if($result['ok']){
    $m=$result['metrics'];
    fwrite(STDOUT,"[Nexora Transfer Contracts] PASS — {$m['transfer_surfaces']} transfer surfaces; {$m['archive_budget_profiles']} archive budgets; {$m['unbounded_archive_extracts']} unbounded archive extracts; {$m['unsafe_backup_full_loads']} full-backup memory loads.\n");
    exit(0);
}
fwrite(STDERR,"[Nexora Transfer Contracts] FAIL\n");
foreach($result['errors'] as $error)fwrite(STDERR," - {$error}\n");
exit(1);
