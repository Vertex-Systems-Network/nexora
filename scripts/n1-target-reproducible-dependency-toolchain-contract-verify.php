<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-reproducible-dependency-toolchain-contracts.php';
$result = nexoraAnalyzeReproducibleDependencyToolchainContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Reproducible Dependency Toolchain Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Reproducible Dependency Toolchain Contracts] PASS — double-run lock generation, exact toolchain binding and locked-install immutability align without changing target denominator.\n",
);
