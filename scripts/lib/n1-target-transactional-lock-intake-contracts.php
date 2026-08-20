<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTransactionalLockIntakeContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'scripts/lib/dependency-lock-intake.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/dependency-lock-promote.php',
        'scripts/refresh-dependency-locks.bat',
        'scripts/refresh-dependency-locks.ps1',
        'scripts/refresh-dependency-locks.sh',
        'scripts/promote-reviewed-dependency-locks.bat',
        'scripts/promote-reviewed-dependency-locks.ps1',
        'scripts/promote-reviewed-dependency-locks.sh',
        'scripts/dependency-lock-promotion-recover.php',
        'scripts/recover-dependency-lock-promotion.bat',
        'scripts/recover-dependency-lock-promotion.ps1',
        'scripts/recover-dependency-lock-promotion.sh',
        'tests/Architecture/N100V511TransactionalLockIntakeArchitectureTest.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.11 transactional dependency intake artifact missing [{$file}]";
        }
    }

    $platform = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($platform['version'] ?? '0.0.0'), '1.0.0-rc.76', '<')) {
        $errors[] = 'v5.11 transactional dependency intake requires platform 1.0.0-rc.76 or newer';
    }

    $refresh = $read('scripts/dependency-lock-refresh.php');
    foreach ([
        "--confirm=REFRESH",
        "'/lock-refresh-runs/'",
        "'/candidates'",
        "'root_lockfiles_mutated' => false",
        "nexoraValidateDependencyLockPair(",
        "nexoraDependencyVersionDiff(",
        "PROMOTE-REVIEWED",
    ] as $marker) {
        if (! str_contains($refresh, $marker)) {
            $errors[] = "v5.11 staged lock refresh boundary missing [{$marker}]";
        }
    }

    $currentVersion = (string) ($platform['version'] ?? '0.0.0');
    $refreshModes = version_compare($currentVersion, '1.0.0-rc.77', '>=')
        ? ["'mode' => 'double-run-reproducible-candidate-refresh'", '$runA = $generate(\'A\');', '$runB = $generate(\'B\');']
        : ["'mode' => 'isolated-candidate-refresh'", "'/workspace'"];
    foreach ($refreshModes as $marker) {
        if (! str_contains($refresh, $marker)) {
            $errors[] = "v5.11+ staged lock refresh boundary missing [{$marker}]";
        }
    }
    if (str_contains($refresh, "nexoraWriteFileReplace($root.'/composer.lock'")
        || str_contains($refresh, "nexoraWriteFileReplace($root.'/package-lock.json'")) {
        $errors[] = 'dependency lock refresh must never promote root lockfiles directly';
    }

    $promote = $read('scripts/dependency-lock-promote.php');
    foreach ([
        "PROMOTE-REVIEWED",
        'nexoraCaptureFileSnapshot($composerPath)',
        'nexoraCaptureFileSnapshot($npmPath)',
        'nexoraWriteFileReplace($composerPath',
        'nexoraWriteFileReplace($npmPath',
        "'scripts/dependency-contract-verify.php', '--strict-locks'",
        "'scripts/dependency-lock-review.php'",
        "'--confirm=REVIEWED'",
        "'--require-refresh-handoff'",
        'nexoraRestoreFileSnapshot',
        "'status' => \$promoted ? 'reviewed-promoted' : 'rolled-back'",
        "'rollback_verified'",
        '$writeJournal(\'prepared\')',
        '$writeJournal(\'composer-promoted\')',
        '$writeJournal(\'pair-promoted\')',
        '$writeJournal(\'strict-validated\')',
        '$writeJournal(\'review-attested\')',
        '$writeJournal(\'complete\')',
    ] as $marker) {
        if (! str_contains($promote, $marker)) {
            $errors[] = "v5.11 reviewed lock promotion boundary missing [{$marker}]";
        }
    }

    $library = $read('scripts/lib/dependency-lock-intake.php');
    foreach ([
        'nexoraValidateDependencyLockPair',
        'lockfileVersion',
        'integrity',
        'git\\+|git:|file:|workspace:',
        "version_compare(\$laravelFrameworkLockedVersion, '13.24.0'",
        "version_compare(\$laravelFrameworkLockedVersion, '14.0.0'",
        'nexoraDependencyVersionDiff',
        'nexoraWriteFileReplace',
        'nexoraRestoreFileSnapshot',
    ] as $marker) {
        if (! str_contains($library, $marker)) {
            $errors[] = "v5.11 dependency candidate validation/rollback library missing [{$marker}]";
        }
    }

    $recovery = $read('scripts/dependency-lock-promotion-recover.php');
    foreach ([
        '--confirm=ROLLBACK',
        'promotion_directory',
        'Durable backup hash mismatch',
        "'rolled-back'",
        'recovery_verified',
    ] as $marker) {
        if (! str_contains($recovery, $marker)) {
            $errors[] = "v5.11 crash-recovery boundary missing [{$marker}]";
        }
    }

    $fastTrack = $read('scripts/n1-target-fast-track.php');
    foreach ([
        'refresh-dependency-locks.bat --confirm=REFRESH',
        'promote-reviewed-dependency-locks.bat',
        'PROMOTE-REVIEWED',
        'recover-dependency-lock-promotion.bat --confirm=ROLLBACK',
    ] as $marker) {
        if (! str_contains($fastTrack, $marker)) {
            $errors[] = "v5.11 fast-track lock intake guidance missing [{$marker}]";
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
        'metrics' => [
            'transaction_phases' => 2,
            'root_lockfiles_mutated_during_refresh' => 0,
            'rollback_root_lockfiles' => 2,
            'durable_promotion_stages' => 6,
            'crash_recovery_command' => 1,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
