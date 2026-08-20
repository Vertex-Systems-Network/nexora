<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-semantic-lock-reproducibility-contracts.php';
$result = nexoraAnalyzeSemanticLockReproducibilityContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Semantic Lock Reproducibility Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Semantic Lock Reproducibility Contracts] PASS - independent A/B generation compares canonical lock semantics while exact candidate raw hashes remain sealed for promotion.\n");
