<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-process-plane-contracts.php';$r=nexoraAnalyzeProcessPlaneContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Process Plane Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Process Plane Contracts] PASS — web/queue/scheduler role leases, HA quorums, schema-13 process-policy fencing and release bindings are aligned.\n");
