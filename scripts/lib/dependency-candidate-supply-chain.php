<?php

declare(strict_types=1);

require_once __DIR__.'/dependency-lock-intake.php';

/** @return array<string,mixed> */
function nexoraDependencyCandidateSupplyChainPolicy(string $root): array
{
    $config = require $root.'/config/nexora-supply-chain.php';
    return (array) ($config['dependency_candidate'] ?? []);
}

/** @return array<string,mixed> */
function nexoraDependencyCandidateProvenance(string $root, string $composerLockPath, string $npmLockPath): array
{
    $policy = nexoraDependencyCandidateSupplyChainPolicy($root);
    $errors = [];
    $composerHosts = [];
    $npmHosts = [];
    $composerUrls = 0;
    $npmUrls = 0;
    $npmIntegrityMissing = 0;
    $npmBundledIntegrityCovered = 0;

    $decode = static function (string $path, string $label, array &$errors): array {
        if (! is_file($path)) {
            $errors[] = "{$label} missing.";
            return [];
        }
        try {
            $decoded = json_decode((string) file_get_contents($path), true, 2048, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $errors[] = "{$label} invalid JSON: {$e->getMessage()}";
            return [];
        }
        return is_array($decoded) ? $decoded : [];
    };

    $checkUrl = static function (string $url, array $allowedHosts, string $label, array &$hosts, array &$errors): void {
        if ($url === '') return;
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https') {
            $errors[] = "{$label} must use HTTPS; observed scheme [{$scheme}].";
            return;
        }
        if (isset($parts['user']) || isset($parts['pass']) || str_contains($url, '@') && preg_match('#https://[^/]*@#i', $url) === 1) {
            $errors[] = "{$label} URL contains embedded credentials.";
        }
        if ($host === '') {
            $errors[] = "{$label} URL host is missing.";
            return;
        }
        $hosts[$host] = true;
        if ($allowedHosts !== [] && ! in_array($host, $allowedHosts, true)) {
            $errors[] = "{$label} host is outside the trusted registry/source allowlist: {$host}";
        }
    };

    $composer = $decode($composerLockPath, 'composer.lock', $errors);
    $npm = $decode($npmLockPath, 'package-lock.json', $errors);
    $allowedComposer = array_values(array_map('strtolower', (array) ($policy['composer_allowed_hosts'] ?? [])));
    $allowedNpm = array_values(array_map('strtolower', (array) ($policy['npm_allowed_hosts'] ?? [])));

    foreach (array_merge((array) ($composer['packages'] ?? []), (array) ($composer['packages-dev'] ?? [])) as $package) {
        if (! is_array($package)) continue;
        foreach (['dist', 'source'] as $kind) {
            $url = (string) (($package[$kind]['url'] ?? ''));
            if ($url === '') continue;
            $composerUrls++;
            $name = (string) ($package['name'] ?? 'unknown');
            $checkUrl($url, $allowedComposer, "Composer {$name} {$kind}", $composerHosts, $errors);
        }
    }

    $npmPackages = (array) ($npm['packages'] ?? []);
    foreach ($npmPackages as $path => $package) {
        if ($path === '' || ! is_array($package)) continue;
        if (($package['link'] ?? false) === true) {
            $errors[] = "npm {$path} uses a link package.";
            continue;
        }
        $resolved = (string) ($package['resolved'] ?? '');
        if ($resolved !== '') {
            $npmUrls++;
            $checkUrl($resolved, $allowedNpm, "npm {$path} resolved", $npmHosts, $errors);
        }

        $coverage = nexoraNpmPackageIntegrityCoverage($npmPackages, (string) $path, $package);
        if (($coverage['status'] ?? null) !== 'pass') {
            $npmIntegrityMissing++;
            $errors[] = "npm {$path} integrity coverage failed: ".(string) ($coverage['error'] ?? 'unknown');
        } elseif (($coverage['mode'] ?? null) === 'bundled') {
            $npmBundledIntegrityCovered++;
        }
    }

    ksort($composerHosts, SORT_STRING);
    ksort($npmHosts, SORT_STRING);
    $semanticDigests = nexoraDependencyLockSemanticDigests($composerLockPath, $npmLockPath);
    $summary = [
        'schema' => 2,
        'composer_lock_sha256' => nexoraHashOptionalFile($composerLockPath),
        'package_lock_sha256' => nexoraHashOptionalFile($npmLockPath),
        'composer_lock_semantic_sha256' => $semanticDigests['composer_lock_semantic_sha256'],
        'package_lock_semantic_sha256' => $semanticDigests['package_lock_semantic_sha256'],
        'composer_urls_checked' => $composerUrls,
        'npm_urls_checked' => $npmUrls,
        'composer_hosts' => array_keys($composerHosts),
        'npm_hosts' => array_keys($npmHosts),
        'npm_integrity_missing' => $npmIntegrityMissing,
        'npm_integrity_bundled_covered' => $npmBundledIntegrityCovered,
        'errors' => array_values(array_unique($errors)),
    ];
    $fingerprintPayload = $summary;
    unset(
        $fingerprintPayload['errors'],
        $fingerprintPayload['composer_lock_sha256'],
        $fingerprintPayload['package_lock_sha256'],
    );
    $summary['fingerprint_sha256'] = hash('sha256', json_encode($fingerprintPayload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    $summary['status'] = $errors === [] ? 'pass' : 'fail';
    return $summary;
}

/** @return array<string,mixed> */
function nexoraRunDependencyCandidateSupplyChain(
    string $root,
    string $workspace,
    array $composerCommand,
    array $environment,
): array {
    $provenance = nexoraDependencyCandidateProvenance($root, $workspace.'/composer.lock', $workspace.'/package-lock.json');
    $errors = (array) ($provenance['errors'] ?? []);
    $audit = [
        'composer' => ['exit_code' => null, 'stdout_sha256' => null, 'stderr_sha256' => null],
        'npm' => ['exit_code' => null, 'stdout_sha256' => null, 'stderr_sha256' => null],
    ];

    if ($errors === []) {
        $composer = nexoraRunTargetCommand(
            array_merge($composerCommand, ['audit', '--locked', '--no-interaction', '--format=json']),
            $workspace,
            $environment,
        );
        $audit['composer'] = [
            'exit_code' => $composer['exit_code'],
            'stdout_sha256' => hash('sha256', (string) $composer['stdout']),
            'stderr_sha256' => hash('sha256', (string) $composer['stderr']),
        ];
        if ($composer['exit_code'] !== 0) {
            $errors[] = 'Composer candidate audit reported vulnerable/blocked locked dependencies.';
        }
    }

    if ($errors === []) {
        $npm = nexoraRunTargetCommand(
            ['npm', 'audit', '--package-lock-only', '--audit-level=high', '--json'],
            $workspace,
            $environment,
        );
        $audit['npm'] = [
            'exit_code' => $npm['exit_code'],
            'stdout_sha256' => hash('sha256', (string) $npm['stdout']),
            'stderr_sha256' => hash('sha256', (string) $npm['stderr']),
        ];
        if ($npm['exit_code'] !== 0) {
            $errors[] = 'npm candidate audit reported high-or-higher vulnerabilities or audit failure.';
        }
    }

    $evidence = [
        'schema' => 1,
        'status' => $errors === [] ? 'pass' : 'fail',
        'provenance' => $provenance,
        'audit' => $audit,
        'errors' => array_values(array_unique($errors)),
    ];
    $fingerprintPayload = [
        'provenance_fingerprint_sha256' => $provenance['fingerprint_sha256'] ?? null,
        'composer_audit_exit_code' => $audit['composer']['exit_code'],
        'npm_audit_exit_code' => $audit['npm']['exit_code'],
    ];
    $evidence['fingerprint_sha256'] = hash('sha256', json_encode($fingerprintPayload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    return $evidence;
}
