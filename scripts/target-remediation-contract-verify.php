<?php

declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root.'/scripts/lib/target-remediation-contracts.php';
$result = nexoraAnalyzeTargetRemediationContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Target Remediation Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Target Remediation Contracts] PASS — {$result['metrics']['wrappers']} wrappers; automatic downloads {$result['metrics']['automatic_downloads']}; automatic lock acceptance {$result['metrics']['automatic_lock_acceptance']}; checksum guards {$result['metrics']['php_ini_checksum_guards']}\n");
