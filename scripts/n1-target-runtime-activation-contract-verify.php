<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-runtime-activation-contracts.php';$r=nexoraAnalyzeRuntimeActivationContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Runtime Activation Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Runtime Activation Contracts] PASS — activation epoch, cache snapshot, process fence and schema-6-compatible queue activation are enforced.\n");
