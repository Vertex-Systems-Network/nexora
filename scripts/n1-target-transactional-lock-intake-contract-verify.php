<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-transactional-lock-intake-contracts.php';
$result = nexoraAnalyzeTransactionalLockIntakeContracts($root);

foreach ($result['warnings'] as $warning) {
    fwrite(STDOUT, "[Nexora Transactional Lock Intake Contracts] WARNING — {$warning}\n");
}
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Transactional Lock Intake Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Transactional Lock Intake Contracts] PASS — isolated candidate refresh, explicit reviewed promotion, pair rollback and unchanged 105-gate target denominator align.\n");
