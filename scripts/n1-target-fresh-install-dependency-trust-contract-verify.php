<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-fresh-install-dependency-trust-contracts.php';

$result = nexoraAnalyzeFreshInstallDependencyTrustContracts($root);
if ($result['errors'] !== []) {
    fwrite(
        STDERR,
        "[Nexora Fresh Install Dependency Trust Contracts] FAIL\n - "
            .implode("\n - ", $result['errors'])."\n",
    );
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Fresh Install Dependency Trust Contracts] PASS — deterministic bootstrap identity, strict review separation, installer preflight and provenance sync aligned.\n",
);
