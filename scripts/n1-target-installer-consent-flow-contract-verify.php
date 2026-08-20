<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/n1-target-installer-consent-flow-contracts.php';

$result = nexoraAnalyzeInstallerConsentFlowContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Installer Consent Flow Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Installer Consent Flow Contracts] PASS — dependency preflight, DB resume/reset consent, password-risk consent and final CTA are aligned.\n");
