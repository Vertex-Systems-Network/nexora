<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-source-set-handshake-contracts.php';
$result = nexoraAnalyzeTargetSourceSetHandshakeContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Source Set + Web Ack Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Source Set + Web Ack Contracts] PASS — critical source manifest, CLI/web activation nonce and installation progress visibility align without changing target denominator.\n",
);
