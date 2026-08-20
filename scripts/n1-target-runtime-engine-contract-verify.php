<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-runtime-engine-contracts.php';$r=nexoraAnalyzeRuntimeEngineContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Runtime Engine Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Runtime Engine Contracts] PASS — PHP patch, extension profile, PDO drivers and schema-6 queue engine fencing aligned.\n");
