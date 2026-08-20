<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/zero-install-contracts.php';
$result = nexoraAnalyzeZeroInstallContracts($root);
if (! $result['ok']) {
    fwrite(STDERR, "[Nexora Zero Install Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, '[Nexora Zero Install Contracts] PASS — '.$result['metrics']['required_artifacts'].' artifacts; '.$result['metrics']['recovery_layers']." recovery layers; true-zero runners aligned.\n");
