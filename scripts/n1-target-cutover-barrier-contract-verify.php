<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-cutover-barrier-contracts.php';$r=nexoraAnalyzeCutoverBarrierContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora v3.2 Cutover Barrier Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora v3.2 Cutover Barrier Contracts] PASS — atomic runtime admission barrier, exact queue payload fencing and frontend Inertia v3 regressions are source-guarded.\n");
