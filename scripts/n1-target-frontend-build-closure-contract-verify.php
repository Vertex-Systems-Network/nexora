<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-frontend-build-closure-contracts.php';
$result = nexoraAnalyzeFrontendBuildClosureContracts($root);

if ($result['errors'] !== []) {
    fwrite(
        STDERR,
        "[Nexora Frontend Build Closure Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n",
    );
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "[Nexora Frontend Build Closure Contracts] PASS — %d historical diagnostics / %d files; C1 %d gates; target denominator unchanged.\n",
        $result['metrics']['historical_errors'],
        $result['metrics']['historical_files'],
        $result['metrics']['c1_target_gates'],
    ),
);
