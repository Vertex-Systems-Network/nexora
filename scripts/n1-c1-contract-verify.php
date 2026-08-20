<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-c1-contracts.php';
$result = nexoraAnalyzeN10C1Contracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[N1.0-C1 Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "[N1.0-C1 Contracts] PASS — %d wrappers; %d certification gates; %d setup actions; %d frontend diagnostics; lock refresh %d; lock acceptance %d.\n",
        $result['metrics']['wrappers'],
        $result['metrics']['certification_gates'],
        $result['metrics']['setup_actions'],
        $result['metrics']['frontend_diagnostic_artifacts'],
        $result['metrics']['automatic_lock_refresh'],
        $result['metrics']['automatic_lock_acceptance'],
    ),
);
