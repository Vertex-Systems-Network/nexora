<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeN10C1Contracts(string $root): array
{
    $errors = [];
    $runnerPath = $root.'/scripts/n1-c1-dependency-certify.php';
    $runner = is_file($runnerPath) ? (string) file_get_contents($runnerPath) : '';

    $required = [
        'scripts/n1-c1-dependency-certify.php',
        'scripts/n1-c1-dependency-certify.bat',
        'scripts/n1-c1-dependency-certify.ps1',
        'scripts/n1-c1-dependency-certify.sh',
        'scripts/n1-c1-installed-dependency-verify.php',
        'scripts/lib/n1-frontend-build-diagnostics.php',
        'scripts/n1-c1-frontend-build-doctor.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file)) {
            $errors[] = "Missing {$file}.";
        }
    }

    $certificationGates = [
        'prerequisite-intake',
        'reviewed-locks',
        'strict-locks',
        'runtime-policy',
        'installed-state',
        'inertia-contract',
        'typecheck',
        'frontend-tests',
        'vite-build',
        'dependency-provenance',
        'dependency-audit',
        'dependency-sbom',
        'asset-budgets',
        'toolchain-freeze',
    ];
    foreach ($certificationGates as $gate) {
        if (! str_contains($runner, "'{$gate}'")) {
            $errors[] = "C1 runner missing certification gate [{$gate}].";
        }
    }

    foreach (['composer-install', 'npm-ci'] as $setupAction) {
        if (! str_contains($runner, "'{$setupAction}'")) {
            $errors[] = "C1 runner missing optional setup action [{$setupAction}].";
        }
    }

    foreach ([
        'attachFrontendDiagnostics',
        'typecheck.diagnostics.json',
        'vite-build.diagnostics.json',
        'frontend_typecheck_diagnostics_sha256',
        'frontend_vite_build_diagnostics_sha256',
    ] as $marker) {
        if (! str_contains($runner, $marker)) {
            $errors[] = "C1 runner missing frontend failure intake [{$marker}].";
        }
    }

    if (! str_contains($runner, "'--no-scripts'")) {
        $errors[] = 'C1 Composer install must not execute application runtime scripts; C2 owns Laravel runtime certification.';
    }

    foreach (['composer update', 'npm install', '--confirm=REVIEWED', '--accept'] as $forbidden) {
        if (stripos($runner, $forbidden) !== false) {
            $errors[] = "C1 runner must not refresh/accept dependency locks [{$forbidden}].";
        }
    }

    $releasePolicy = (string) @file_get_contents($root.'/config/nexora-release.php');
    if (! str_contains($releasePolicy, 'storage/app/nexora/n1-c1/')) {
        $errors[] = 'Release policy must exclude C1 runtime evidence.';
    }

    $zeroState = (string) @file_get_contents($root.'/scripts/zero-state-verify.php');
    if (! str_contains($zeroState, 'storage/app/nexora/n1-c1')) {
        $errors[] = 'Strict zero-state must reject C1 runtime evidence.';
    }

    return [
        'errors' => $errors,
        'warnings' => [],
        'metrics' => [
            'wrappers' => count(array_filter([
                'scripts/n1-c1-dependency-certify.bat',
                'scripts/n1-c1-dependency-certify.ps1',
                'scripts/n1-c1-dependency-certify.sh',
            ], static fn (string $file): bool => is_file($root.'/'.$file))),
            'certification_gates' => count($certificationGates),
            'ordered_gates' => count($certificationGates),
            'setup_actions' => 2,
            'frontend_diagnostic_artifacts' => 2,
            'automatic_lock_refresh' => preg_match('/composer update|npm install/i', $runner) === 1 ? 1 : 0,
            'automatic_lock_acceptance' => preg_match('/--accept|--confirm=REVIEWED/i', $runner) === 1 ? 1 : 0,
        ],
    ];
}
