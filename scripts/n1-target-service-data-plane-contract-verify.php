<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-service-data-plane-contracts.php';$r=nexoraAnalyzeServiceDataPlaneContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Service Data Plane Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Service Data Plane Contracts] PASS — cache/session/queue/mail/TLS/proxy identity + approved outbound network policy aligned.\n");
