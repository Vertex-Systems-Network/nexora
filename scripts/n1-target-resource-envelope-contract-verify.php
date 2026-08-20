<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-resource-envelope-contracts.php';$r=nexoraAnalyzeResourceEnvelopeContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Resource Envelope Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Resource Envelope Contracts] PASS — live capacity admission, queue policy fencing, upgrade/backup guards and HA resource convergence are aligned.\n");
