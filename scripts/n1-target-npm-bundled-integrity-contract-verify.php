<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-npm-bundled-integrity-contracts.php';
$result = nexoraAnalyzeNpmBundledIntegrityContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora npm Bundled Integrity Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora npm Bundled Integrity Contracts] PASS - package-lock v3 inBundle children require explicit bundle-owner membership plus owner resolved+integrity coverage; external packages still require direct integrity. C1=14, total=105 unchanged.\n");
