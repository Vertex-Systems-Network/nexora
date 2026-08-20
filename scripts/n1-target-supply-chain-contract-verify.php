<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-supply-chain-contracts.php';$r=nexoraAnalyzeN10TargetSupplyChainContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[N1.0 Supply Chain Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[N1.0 Supply Chain Contracts] PASS — trust anchor, pinned offline identity, SBOM, no-dev runtime stage, provenance and content manifest enforced.\n");
