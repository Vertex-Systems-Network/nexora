<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-framework-dependency-contracts.php';

$result = nexoraAnalyzeFrameworkDependencyContracts($root);

if ($result['errors'] !== []) {
    fwrite(
        STDERR,
        "[Nexora Framework/Dependency Contracts] FAIL\n - "
        .implode("\n - ", $result['errors'])."\n",
    );
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Framework/Dependency Contracts] PASS — Laravel 13.24+ <14, reviewed locks, '
    .'safe generation reconciliation, and human-readable critical code are aligned.'.PHP_EOL,
);
