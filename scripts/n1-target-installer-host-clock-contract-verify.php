<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-installer-host-clock-contracts.php';
$result = nexoraAnalyzeInstallerHostClockContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Installer Host/Clock Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    "[Nexora Installer Host/Clock Contracts] PASS — early installer-safe host attestation, Windows portability and strict C2/C6 separation align.\n",
);
