<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-progress-visibility-contracts.php';
$result = nexoraAnalyzeTargetProgressVisibilityContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Target Progress Visibility Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Target Progress Visibility Contracts] PASS — 105 granular target gates, strict six-chunk truth and 76-error TypeScript remediation ledger are aligned.\n");
