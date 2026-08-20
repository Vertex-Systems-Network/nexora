<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-installation-commit-contracts.php';
$result = nexoraAnalyzeInstallationCommitContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Installation Commit Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Installation Commit Contracts] PASS — sealed lock, staged dependency receipt, fail-closed corruption and post-commit recovery aligned.\n");
