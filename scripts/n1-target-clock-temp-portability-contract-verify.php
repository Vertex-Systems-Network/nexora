<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-clock-temp-portability-contracts.php';
$result = nexoraAnalyzeClockTempPortabilityContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Clock + Temp Portability Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Clock + Temp Portability Contracts] PASS — MySQL epoch semantics, Windows-safe installer temp fallback and strict certification separation align.\n",
);
