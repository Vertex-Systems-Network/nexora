<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-distributed-upgrade-contracts.php';$r=nexoraAnalyzeDistributedUpgradeContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Distributed Upgrade Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Distributed Upgrade Contracts] PASS — DB-backed global lease, migration ledger convergence and explicit peer quiescence aligned.\n");
