<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-tenant-seed-typescript-contracts.php';

$result = nexoraAnalyzeTenantSeedTypeScriptContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Tenant Seed + TypeScript Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Tenant Seed + TypeScript Contracts] PASS — stale tenant reset, scoped default seeds, '
    .'tenant-write fail-closed behavior and historical frontend regression boundaries are aligned.'.PHP_EOL,
);
