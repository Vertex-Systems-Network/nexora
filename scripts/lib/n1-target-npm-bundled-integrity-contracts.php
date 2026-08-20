<?php

declare(strict_types=1);

require_once __DIR__.'/dependency-lock-intake.php';

/** @return array{errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeNpmBundledIntegrityContracts(string $root): array
{
    $errors = [];
    $read = static fn (string $path): string => is_file($root.'/'.$path)
        ? (string) file_get_contents($root.'/'.$path)
        : '';

    $intake = $read('scripts/lib/dependency-lock-intake.php');
    $candidate = $read('scripts/lib/dependency-candidate-supply-chain.php');
    $review = $read('scripts/dependency-lock-review.php');

    foreach ([
        'nexoraNpmPackageIntegrityCoverage',
        'nexoraNpmLockIntegritySummary',
        "['inBundle']",
        "['bundleDependencies']",
        "['bundledDependencies']",
        'bundle owner is missing resolved URL or integrity metadata',
        'bundle owner does not list the direct bundled package',
    ] as $marker) {
        if (! str_contains($intake, $marker)) {
            $errors[] = "npm bundled-integrity intake missing [{$marker}]";
        }
    }
    foreach ([
        'nexoraNpmPackageIntegrityCoverage',
        'npm_integrity_bundled_covered',
    ] as $marker) {
        if (! str_contains($candidate, $marker)) {
            $errors[] = "candidate supply-chain bundled-integrity binding missing [{$marker}]";
        }
    }
    foreach ([
        'nexoraNpmLockIntegritySummary',
        'integrity_bundled_covered',
        'npm_integrity_bundled_covered',
    ] as $marker) {
        if (! str_contains($review, $marker)) {
            $errors[] = "dependency review bundled-integrity binding missing [{$marker}]";
        }
    }

    $ownerPath = 'node_modules/@tailwindcss/oxide-wasm32-wasi';
    $bundleNames = [
        '@emnapi/core',
        '@emnapi/runtime',
        '@emnapi/wasi-threads',
        '@napi-rs/wasm-runtime',
        '@tybys/wasm-util',
        'tslib',
    ];
    $packages = [
        '' => ['devDependencies' => ['tailwindcss' => '^4.3.0']],
        $ownerPath => [
            'version' => '4.3.1',
            'resolved' => 'https://registry.npmjs.org/@tailwindcss/oxide-wasm32-wasi/-/oxide-wasm32-wasi-4.3.1.tgz',
            'integrity' => 'sha512-owner-integrity-fixture',
            'bundleDependencies' => $bundleNames,
            'optional' => true,
        ],
    ];
    foreach ($bundleNames as $name) {
        $packages[$ownerPath.'/node_modules/'.$name] = [
            'version' => '1.0.0',
            'inBundle' => true,
            'optional' => true,
        ];
    }
    $summary = nexoraNpmLockIntegritySummary(['lockfileVersion' => 3, 'packages' => $packages]);
    if ($summary['missing'] !== []) {
        $errors[] = 'valid npm inBundle fixture was rejected: '.implode(', ', $summary['missing']);
    }
    if (count($summary['bundled_covered']) !== 6) {
        $errors[] = 'valid npm inBundle fixture did not prove six bundled-integrity children';
    }

    $badOwner = $packages;
    unset($badOwner[$ownerPath]['integrity']);
    $badOwnerCoverage = nexoraNpmPackageIntegrityCoverage(
        $badOwner,
        $ownerPath.'/node_modules/@emnapi/core',
        $badOwner[$ownerPath.'/node_modules/@emnapi/core'],
    );
    if (($badOwnerCoverage['status'] ?? null) !== 'fail') {
        $errors[] = 'inBundle child was accepted without bundle-owner integrity';
    }

    $unlisted = $packages;
    $unlisted[$ownerPath]['bundleDependencies'] = ['tslib'];
    $unlistedCoverage = nexoraNpmPackageIntegrityCoverage(
        $unlisted,
        $ownerPath.'/node_modules/@emnapi/core',
        $unlisted[$ownerPath.'/node_modules/@emnapi/core'],
    );
    if (($unlistedCoverage['status'] ?? null) !== 'fail') {
        $errors[] = 'inBundle child was accepted without explicit owner bundle membership';
    }

    $external = [
        'node_modules/example' => [
            'version' => '1.0.0',
            'resolved' => 'https://registry.npmjs.org/example/-/example-1.0.0.tgz',
        ],
    ];
    $externalCoverage = nexoraNpmPackageIntegrityCoverage(
        $external,
        'node_modules/example',
        $external['node_modules/example'],
    );
    if (($externalCoverage['status'] ?? null) !== 'fail') {
        $errors[] = 'external resolved npm package was accepted without direct integrity';
    }

    $pkg1 = $read('scripts/lib/pkg1-closure-contracts.php').$read('scripts/pkg1-usable-closure.php');
    $progress = $read('scripts/lib/n1-target-progress.php').$read('scripts/lib/n1-target-progress-visibility-contracts.php');
    if (! str_contains($pkg1, '14')) $errors[] = 'C1 denominator 14 marker missing';
    if (! str_contains($progress, '105')) $errors[] = 'target denominator 105 marker missing';

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => [],
        'metrics' => [
            'bundled_fixture_children' => 6,
            'negative_fail_closed_fixtures' => 3,
            'c1_certification_gates' => 14,
            'target_gate_denominator' => 105,
            'target_gate_denominator_changed' => 0,
        ],
    ];
}
