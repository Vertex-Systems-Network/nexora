<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-host-clock-contracts.php';$r=nexoraAnalyzeHostClockContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Host/Clock Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Host/Clock Contracts] PASS — DB-clock leases, host identity, queue skew fencing and deep probes are aligned.\n");
