<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-dependency-candidate-supply-chain-contracts.php';
$result = nexoraAnalyzeDependencyCandidateSupplyChainContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Dependency Candidate Supply-Chain Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Dependency Candidate Supply-Chain Contracts] PASS — candidate registries/origins, Composer+npm audits, promotion revalidation and reviewed-lock provenance are bound without changing the 105-gate denominator.\n");
