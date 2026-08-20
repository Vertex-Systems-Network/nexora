<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-runtime-environment-contracts.php';$r=nexoraAnalyzeRuntimeEnvironmentContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Runtime Environment Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Runtime Environment Contracts] PASS — environment fingerprint, APP_KEY continuity and schema-4 queue fencing enforced.\n");
