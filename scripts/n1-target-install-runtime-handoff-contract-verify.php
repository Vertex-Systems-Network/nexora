<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-install-runtime-handoff-contracts.php';

$result = nexoraAnalyzeInstallRuntimeHandoffContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Install Runtime Handoff Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Install Runtime Handoff Contracts] PASS — full source-tree deployment binding, runtime-admission split and post-install handoff are aligned.\n");
