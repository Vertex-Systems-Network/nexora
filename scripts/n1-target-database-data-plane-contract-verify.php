<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-database-data-plane-contracts.php';$r=nexoraAnalyzeDatabaseDataPlaneContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Database Data Plane Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Database Data Plane Contracts] PASS — server/session identity, schema attestation, backup binding and forward-compatible database queue fencing aligned (current schema 13).\n");
