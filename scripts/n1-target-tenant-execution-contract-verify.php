<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-tenant-execution-contracts.php';

$result = nexoraAnalyzeTenantExecutionContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Tenant Execution Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Tenant Execution Contracts] PASS — queue/scheduler tenant isolation, active-tenant enforcement, transactional defaults and C2/C4 regression boundaries align.' . PHP_EOL,
);
