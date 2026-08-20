<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-runtime-source-convergence-contracts.php';
$result = nexoraAnalyzeRuntimeSourceConvergenceContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Runtime Source Convergence Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Runtime Source Convergence Contracts] PASS — loaded critical PHP generations, secure one-time web acknowledgement and redacted public diagnostics align without changing 105 target gates.\n");
