<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-runtime-quiescence-contracts.php';$r=nexoraAnalyzeRuntimeQuiescenceContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Runtime Quiescence Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Runtime Quiescence Contracts] PASS — in-flight activity, mixed-version fencing and migration safety are fail-closed.\n");
