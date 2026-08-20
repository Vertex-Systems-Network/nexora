<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-policy-plane-contracts.php';$r=nexoraAnalyzePolicyPlaneContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Policy Plane Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Policy Plane Contracts] PASS — effective fail-closed policy convergence + schema-13 queue fencing aligned.\n");
