<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-windows-npm-bridge-contracts.php';
$result = nexoraAnalyzeWindowsNpmBridgeContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Windows npm Bridge Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Windows npm Bridge Contracts] PASS - Windows npm.cmd/npx.cmd are normalized to node.exe + npm CLI JS, toolchain fingerprints bind the executed payload, and PKG-1 blocks before candidate generation when the full toolchain is not ready.\n");
