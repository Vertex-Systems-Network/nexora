<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-typescript-depth-contracts.php';
$result = nexoraAnalyzeTypeScriptDepthContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora TypeScript Depth Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora TypeScript Depth Contracts] PASS - Automation and Documents recursive Inertia form boundaries are shallow enough to avoid the observed four TS2589 instantiation-depth failures.\n");
