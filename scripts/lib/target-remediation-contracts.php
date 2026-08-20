<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTargetRemediationContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $required = [
        'scripts/target-prerequisite-remediate.php',
        'scripts/target-prerequisite-remediate.bat',
        'scripts/target-prerequisite-remediate.ps1',
        'scripts/target-prerequisite-remediate.sh',
    ];
    foreach ($required as $relative) if (! is_file($root.'/'.$relative)) $errors[] = "Missing {$relative}.";
    $source = (string) @file_get_contents($root.'/scripts/target-prerequisite-remediate.php');
    foreach (['--apply-extensions','php_ini_loaded_file','extension_dir','php_','nexora-target-env.cmd','target-remediation','restart_required','composer_candidates'] as $marker) {
        if (! str_contains($source, $marker)) $errors[] = "Target remediation missing marker [{$marker}].";
    }
    foreach (['curl','Invoke-WebRequest','composer update','npm install','--accept','--confirm=REVIEWED'] as $forbidden) {
        if (stripos($source, $forbidden) !== false) $errors[] = "Target remediation must not contain automatic download/unlocked dependency/lock-acceptance marker [{$forbidden}].";
    }
    if (! str_contains($source, "PHP_OS_FAMILY !== 'Windows'") || ! str_contains($source, '! $laragonDetected')) {
        $errors[] = '--apply-extensions must be restricted to an explicitly detected Windows/Laragon target.';
    }
    if (! str_contains($source, 'hash_file(\'sha256\', $backup)') || ! str_contains($source, 'hash_file(\'sha256\', $ini)')) {
        $errors[] = 'php.ini remediation must checksum-verify backup and published content.';
    }
    $release = (string) @file_get_contents($root.'/config/nexora-release.php');
    if (! str_contains($release, 'storage/app/nexora/target-remediation/')) $errors[] = 'Production release policy must exclude target-remediation runtime evidence.';
    $zero = (string) @file_get_contents($root.'/scripts/zero-state-verify.php');
    if (! str_contains($zero, 'storage/app/nexora/target-remediation')) $errors[] = 'Strict zero-state verification must reject target-remediation runtime evidence.';
    $intake = (string) @file_get_contents($root.'/scripts/target-prerequisite-intake.php');
    if (! str_contains($intake, 'target-prerequisite-remediate.bat')) $errors[] = 'Target intake must point Windows/Laragon operators to the remediation assistant when extensions are missing.';
    $orchestrator = (string) @file_get_contents($root.'/scripts/target-certification-orchestrator.php');
    if (! str_contains($orchestrator, 'target-prerequisite-remediate.bat')) $errors[] = 'Target orchestrator must surface the remediation command after prerequisite failure.';

    return [
        'errors' => $errors,
        'warnings' => $warnings,
        'metrics' => [
            'wrappers' => count(array_filter(array_slice($required, 1), static fn (string $relative): bool => is_file($root.'/'.$relative))),
            'automatic_downloads' => preg_match('/(?:curl|Invoke-WebRequest)/i', $source) === 1 ? 1 : 0,
            'automatic_lock_acceptance' => (str_contains($source, '--accept') || str_contains($source, '--confirm=REVIEWED')) ? 1 : 0,
            'php_ini_checksum_guards' => substr_count($source, "hash_file('sha256'"),
        ],
    ];
}
