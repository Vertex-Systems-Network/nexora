<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeWindowsNpmBridgeContracts(string $root): array
{
    $errors = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    foreach ([
        'scripts/lib/target-composer.php',
        'scripts/lib/dependency-toolchain.php',
        'scripts/pkg1-status.php',
        'scripts/pkg1-run.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/lib/dependency-candidate-supply-chain.php',
    ] as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "Windows npm bridge artifact missing [{$file}]";
        }
    }

    $runner = $read('scripts/lib/target-composer.php');
    foreach ([
        'nexoraNormalizeTargetCommand',
        "'npm', 'npm.cmd', 'npm.bat'",
        "'npx', 'npx.cmd', 'npx.bat'",
        "'npm-cli.js'",
        "'npx-cli.js'",
        "['where.exe', \$name]",
        "['bypass_shell' => true]",
        'return array_merge([$node, $cli], array_slice($command, 1));',
        '$command = nexoraNormalizeTargetCommand($command, $root, $env);',
    ] as $marker) {
        if (! str_contains($runner, $marker)) {
            $errors[] = "Windows npm executable normalization missing [{$marker}]";
        }
    }

    $toolchain = $read('scripts/lib/dependency-toolchain.php');
    foreach ([
        'nexoraNormalizeTargetCommand($versionCommand, $root, $environment)',
        "'execution_mode' => \$executionMode",
        "'launcher' => \$launcherPath",
        "'node-cli'",
        "'npm-cli.js', 'npx-cli.js'",
    ] as $marker) {
        if (! str_contains($toolchain, $marker)) {
            $errors[] = "Windows npm toolchain fingerprint binding missing [{$marker}]";
        }
    }

    $status = $read('scripts/pkg1-status.php');
    foreach ([
        "'BLOCKED_TOOLCHAIN'",
        "'dependency_toolchain_errors'",
        "'npm_execution_mode'",
        "'npm_version'",
        'nexoraCollectDependencyToolchain($root)',
    ] as $marker) {
        if (! str_contains($status, $marker)) {
            $errors[] = "PKG-1 toolchain status admission missing [{$marker}]";
        }
    }

    $launcher = $read('scripts/pkg1-run.php');
    foreach ([
        "if (\$state === 'BLOCKED_TOOLCHAIN')",
        'dependency_toolchain_errors',
        'Fix the toolchain blocker above',
    ] as $marker) {
        if (! str_contains($launcher, $marker)) {
            $errors[] = "PKG-1 launcher toolchain blocker handling missing [{$marker}]";
        }
    }

    $refresh = $read('scripts/dependency-lock-refresh.php');
    if (! str_contains($refresh, "['npm', 'install', '--package-lock-only'")) {
        $errors[] = 'Dependency refresh no longer exposes the npm candidate command through the central command runner.';
    }
    if (! str_contains($refresh, 'nexoraRunTargetCommand($command, $workspace, $environment)')) {
        $errors[] = 'Dependency refresh must execute npm candidate generation through nexoraRunTargetCommand.';
    }

    $supply = $read('scripts/lib/dependency-candidate-supply-chain.php');
    if (! str_contains($supply, "['npm', 'audit', '--package-lock-only', '--audit-level=high', '--json']")) {
        $errors[] = 'Candidate supply-chain admission npm audit command missing.';
    }
    if (! str_contains($supply, 'nexoraRunTargetCommand(')) {
        $errors[] = 'Candidate supply-chain npm audit must execute through nexoraRunTargetCommand.';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'windows_npm_shell_bypass' => 1,
            'npm_execution_payloads_fingerprinted' => 1,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
