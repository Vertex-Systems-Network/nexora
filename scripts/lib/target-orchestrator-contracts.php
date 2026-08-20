<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetOrchestratorContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $required = [
        'scripts/target-certification-orchestrator.php',
        'scripts/target-certification-orchestrator.bat',
        'scripts/target-certification-orchestrator.ps1',
        'scripts/target-certification-orchestrator.sh',
    ];
    foreach ($required as $relative) if (! is_file($root.'/'.$relative)) $errors[] = "Missing {$relative}.";
    $source = (string) @file_get_contents($root.'/scripts/target-certification-orchestrator.php');
    foreach ([
        'target-prerequisite-intake.php',
        'dependency-lock-review.php',
        '--verify-attestation',
        'target-runtime-run.php',
        'closure-dashboard.php',
        'final-target-run.php',
        'target-orchestrator',
        'source_tree_sha256',
        'first_blocker',
    ] as $marker) if (! str_contains($source, $marker)) $errors[] = "Target orchestrator missing marker [{$marker}].";
    if (str_contains($source, '--accept') || str_contains($source, '--confirm=REVIEWED')) $errors[] = 'Target orchestrator must never auto-accept dependency locks.';
    if (preg_match('/\b(?:migrate:fresh|migrate:reset)\b/', $source) === 1) $errors[] = 'Target orchestrator must not directly run destructive migration commands; --full must delegate to the isolated certification runner.';
    if (preg_match('/\b(?:composer\s+update|npm\s+install)\b/i', $source) === 1) $errors[] = 'Target orchestrator must not resolve unlocked dependency graphs.';
    if (! str_contains($source, '$final && ! $full')) $errors[] = '--final must require --full.';
    if (! str_contains($source, "'--seal-evidence'")) $errors[] = 'Operator evidence sealing must be explicit.';

    $release = (string) @file_get_contents($root.'/config/nexora-release.php');
    if (! str_contains($release, 'storage/app/nexora/target-orchestrator/')) $errors[] = 'Production release policy must exclude target-orchestrator runtime evidence.';
    $zero = (string) @file_get_contents($root.'/scripts/zero-state-verify.php');
    if (! str_contains($zero, 'storage/app/nexora/target-orchestrator')) $errors[] = 'Strict zero-state verification must forbid target-orchestrator runtime evidence.';

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'wrappers' => count(array_filter(array_slice($required, 1), static fn (string $relative): bool => is_file($root.'/'.$relative))),
            'ordered_release_gates' => 6,
            'automatic_lock_acceptance' => (str_contains($source, '--accept') || str_contains($source, '--confirm=REVIEWED')) ? 1 : 0,
            'direct_destructive_db_commands' => preg_match('/\b(?:migrate:fresh|migrate:reset)\b/', $source) === 1 ? 1 : 0,
        ],
    ];
}
