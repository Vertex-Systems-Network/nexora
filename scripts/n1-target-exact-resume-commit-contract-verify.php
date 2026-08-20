<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-exact-resume-commit-contracts.php';
$result = nexoraAnalyzeExactResumeCommitContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Exact Resume / Commit Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Exact Resume / Commit Contracts] PASS — full-source resume provenance, final dependency/source snapshot stability and committed-runtime recovery aligned.\n");
