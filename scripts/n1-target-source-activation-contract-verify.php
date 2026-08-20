<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-source-activation-contracts.php';
$result = nexoraAnalyzeTargetSourceActivationContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Source Activation Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Source Activation Contracts] PASS — exact web source generation, installer SHA diagnostics and pre-database stale-source fail-closed behavior align.\n");
