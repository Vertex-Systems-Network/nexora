<?php

declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root.'/scripts/lib/target-orchestrator-contracts.php';
$result = nexoraAnalyzeTargetOrchestratorContracts($root);
if ($result['errors'] !== []) {
    fwrite(STDERR, "[Nexora Target Orchestrator Contracts] FAIL\n - ".implode("\n - ", $result['errors'])."\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Target Orchestrator Contracts] PASS — {$result['metrics']['wrappers']} wrappers; {$result['metrics']['ordered_release_gates']} ordered release gates; no automatic lock acceptance/destructive DB commands.\n");
