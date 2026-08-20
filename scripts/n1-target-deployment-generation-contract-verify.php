<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-deployment-generation-contracts.php';$r=nexoraAnalyzeDeploymentGenerationContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Deployment Generation Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Deployment Generation Contracts] PASS — signed deployment generation, exact queue/client/cache/session fencing and deep integrity verification aligned.\n");
