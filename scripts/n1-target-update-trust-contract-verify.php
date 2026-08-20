<?php

declare(strict_types=1);
$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-target-update-trust-contracts.php';$r=nexoraAnalyzeN10TargetUpdateTrustContracts($root);if($r['errors']!==[]){fwrite(STDERR,"[Nexora Trusted Update Contracts] FAIL\n - ".implode("\n - ",$r['errors'])."\n");exit(1);}fwrite(STDOUT,"[Nexora Trusted Update Contracts] PASS\nWorkflows: {$r['metrics']['trusted_update_workflows']}\nWrappers: {$r['metrics']['cross_platform_wrappers']}\nSeal schema: {$r['metrics']['release_seal_schema']}\nSilent anchor overwrite: {$r['metrics']['silent_anchor_overwrite']}\nDowngrade allowed: {$r['metrics']['downgrade_allowed']}\n");
