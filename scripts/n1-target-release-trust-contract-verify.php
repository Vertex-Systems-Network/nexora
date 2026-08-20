<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-release-trust-contracts.php';$r=nexoraAnalyzeN10TargetReleaseTrustContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[N1.0 Release Trust Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[N1.0 Release Trust Contracts] PASS — toolchain freeze, RSA detached signature, offline verification, archive hygiene and session finalization enforced.\n");
