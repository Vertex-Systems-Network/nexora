<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-storage-data-plane-contracts.php';$r=nexoraAnalyzeStorageDataPlaneContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Storage Data Plane Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Storage Data Plane Contracts] PASS — media/object/backup storage identity, deep probes, queue fencing and HA convergence aligned.\n");
