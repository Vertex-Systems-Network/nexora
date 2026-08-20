<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/pkg1-closure-contracts.php';
$result = nexoraAnalyzePkg1ClosureContracts($root);

if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora PKG-1 Closure Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora PKG-1 Closure Contracts] PASS — C1 14/14 + installer 100% + post-install + live login/admin smoke remain one resumable package boundary; target denominator 105 unchanged.\n");
