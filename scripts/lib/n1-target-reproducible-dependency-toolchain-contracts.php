<?php

declare(strict_types=1);

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeReproducibleDependencyToolchainContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $file): string => is_file($root.'/'.$file)
        ? (string) file_get_contents($root.'/'.$file)
        : '';

    $required = [
        'scripts/lib/dependency-toolchain.php',
        'scripts/dependency-lock-refresh.php',
        'scripts/dependency-lock-promote.php',
        'scripts/dependency-lock-review.php',
        'scripts/n1-c1-dependency-certify.php',
        'tests/Architecture/N100V512ReproducibleDependencyToolchainArchitectureTest.php',
    ];
    foreach ($required as $file) {
        if (! is_file($root.'/'.$file) || filesize($root.'/'.$file) === 0) {
            $errors[] = "v5.12 reproducible dependency-toolchain artifact missing [{$file}]";
        }
    }

    $platform = is_file($root.'/config/nexora.php') ? require $root.'/config/nexora.php' : [];
    if (version_compare((string) ($platform['version'] ?? '0.0.0'), '1.0.0-rc.77', '<')) {
        $errors[] = 'v5.12 requires platform version 1.0.0-rc.77 or newer';
    }

    $toolchain = $read('scripts/lib/dependency-toolchain.php');
    foreach ([
        'nexoraCollectDependencyToolchain',
        'nexoraDependencyToolchainFingerprint',
        "'binary_sha256'",
        "'composer' =>",
        "'node' =>",
        "'npm' =>",
        'nexoraValidateDependencyToolchain',
    ] as $marker) {
        if (! str_contains($toolchain, $marker)) {
            $errors[] = "v5.12 dependency toolchain binding missing [{$marker}]";
        }
    }

    $refresh = $read('scripts/dependency-lock-refresh.php');
    foreach ([
        '$runA = $generate(\'A\');',
        '$runB = $generate(\'B\');',
        "'reproducible' =>",
        "'toolchain_fingerprint_sha256' =>",
        '$rawExactMatch',
        '$semanticExactMatch',
        'workspace A and B canonical dependency digests differ',
        "'raw_exact_match' =>",
        "'semantic_exact_match' =>",
        "'mode' => 'double-run-reproducible-candidate-refresh'",
        "'root_lockfiles_mutated' => false",
    ] as $marker) {
        if (! str_contains($refresh, $marker)) {
            $errors[] = "v5.12 double-run lock refresh missing [{$marker}]";
        }
    }

    $promote = $read('scripts/dependency-lock-promote.php');
    foreach ([
        'candidateToolchainFingerprint',
        'currentToolchainFingerprint',
        'Candidate lock pair does not carry a successful double-run reproducibility proof.',
        'Dependency toolchain fingerprint changed since candidate generation',
        "'toolchain_fingerprint_sha256' =>",
    ] as $marker) {
        if (! str_contains($promote, $marker)) {
            $errors[] = "v5.12 promotion toolchain/reproducibility binding missing [{$marker}]";
        }
    }

    $review = $read('scripts/dependency-lock-review.php');
    foreach ([
        'dependency_toolchain_fingerprint_sha256',
        'Lock-refresh handoff is missing successful double-run reproducibility proof.',
        'Reviewed-lock dependency toolchain fingerprint no longer matches the current toolchain.',
    ] as $marker) {
        if (! str_contains($review, $marker)) {
            $errors[] = "v5.12 reviewed-lock toolchain binding missing [{$marker}]";
        }
    }

    $c1 = $read('scripts/n1-c1-dependency-certify.php');
    foreach ([
        'locked-install-immutability',
        'lockHashesBeforeInstall',
        'lockHashesAfterInstall',
        'toolchainBeforeInstall',
        'toolchainAfterInstall',
        'Reviewed lock hashes or dependency toolchain fingerprint changed during locked installation.',
    ] as $marker) {
        if (! str_contains($c1, $marker)) {
            $errors[] = "v5.12 C1 locked-install immutability missing [{$marker}]";
        }
    }

    if (preg_match('/composer update|npm install/i', $c1) === 1) {
        $errors[] = 'C1 certification runner must never refresh dependency locks';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'candidate_generation_runs' => 2,
            'toolchain_bound_tools' => 4,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
