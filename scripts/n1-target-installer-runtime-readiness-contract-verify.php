<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-installer-runtime-readiness-contracts.php';
$result = nexoraAnalyzeInstallerRuntimeReadinessContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Installer Runtime Readiness Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Installer Runtime Readiness Contracts] PASS — source/dependency/host/resource/policy/process/activation preflight aligns without changing target denominator.\n",
);
