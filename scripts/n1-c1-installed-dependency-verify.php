<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$metrics = [
    'composer_direct' => 0,
    'npm_direct' => 0,
    'laravel_framework_version' => null,
];

$requiredFiles = [
    'composer.lock',
    'package-lock.json',
    'vendor/composer/installed.json',
    'node_modules/.package-lock.json',
];

foreach ($requiredFiles as $relative) {
    if (! is_file($root.'/'.$relative)) {
        $errors[] = "Missing installed dependency artifact [{$relative}].";
    }
}

if ($errors === []) {
    $manifest = decodeDependencyJson($root.'/composer.json');
    $composerLock = decodeDependencyJson($root.'/composer.lock');
    $installedComposer = decodeDependencyJson($root.'/vendor/composer/installed.json');

    $lockedComposerVersions = composerVersionMap($composerLock);
    $installedComposerVersions = installedComposerVersionMap($installedComposer);
    verifyDirectComposerDependencies(
        $manifest,
        $lockedComposerVersions,
        $installedComposerVersions,
        $metrics,
        $errors,
    );
    verifyLaravelRuntimeVersion(
        $lockedComposerVersions,
        $installedComposerVersions,
        $metrics,
        $errors,
    );

    $package = decodeDependencyJson($root.'/package.json');
    $npmLock = decodeDependencyJson($root.'/package-lock.json');
    $npmInstalled = decodeDependencyJson($root.'/node_modules/.package-lock.json');
    verifyDirectNpmDependencies($package, $npmLock, $npmInstalled, $metrics, $errors);
}

if ($errors !== []) {
    fwrite(
        STDERR,
        "[N1.0-C1 Installed Dependencies] FAIL\n - ".implode("\n - ", $errors)."\n",
    );
    exit(1);
}

fwrite(
    STDOUT,
    '[N1.0-C1 Installed Dependencies] PASS — Composer direct '
    .$metrics['composer_direct'].'; npm direct '.$metrics['npm_direct']
    .'; Laravel '.$metrics['laravel_framework_version'].'.'.PHP_EOL,
);

/** @return array<string,mixed> */
function decodeDependencyJson(string $path): array
{
    $decoded = json_decode(
        (string) file_get_contents($path),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    return is_array($decoded) ? $decoded : [];
}

/** @param array<string,mixed> $lock @return array<string,string> */
function composerVersionMap(array $lock): array
{
    $versions = [];
    $packages = array_merge(
        (array) ($lock['packages'] ?? []),
        (array) ($lock['packages-dev'] ?? []),
    );

    foreach ($packages as $package) {
        if (! is_array($package) || ! isset($package['name'], $package['version'])) {
            continue;
        }
        $versions[(string) $package['name']] = (string) $package['version'];
    }

    return $versions;
}

/** @param array<string,mixed> $installed @return array<string,string> */
function installedComposerVersionMap(array $installed): array
{
    $versions = [];
    $rows = is_array($installed['packages'] ?? null)
        ? $installed['packages']
        : $installed;

    foreach ((array) $rows as $package) {
        if (! is_array($package) || ! isset($package['name'])) {
            continue;
        }
        $versions[(string) $package['name']] = (string) (
            $package['pretty_version']
            ?? $package['version']
            ?? ''
        );
    }

    return $versions;
}

/**
 * @param array<string,mixed> $manifest
 * @param array<string,string> $locked
 * @param array<string,string> $installed
 * @param array<string,mixed> $metrics
 * @param list<string> $errors
 */
function verifyDirectComposerDependencies(
    array $manifest,
    array $locked,
    array $installed,
    array &$metrics,
    array &$errors,
): void {
    $direct = array_unique(array_merge(
        array_keys((array) ($manifest['require'] ?? [])),
        array_keys((array) ($manifest['require-dev'] ?? [])),
    ));

    foreach ($direct as $name) {
        if ($name === 'php' || str_starts_with($name, 'ext-')) {
            continue;
        }

        $metrics['composer_direct']++;
        if (! isset($locked[$name])) {
            $errors[] = "Composer direct dependency missing from lock [{$name}].";
            continue;
        }
        if (! isset($installed[$name])) {
            $errors[] = "Composer direct dependency not installed [{$name}].";
            continue;
        }
        if ($installed[$name] !== $locked[$name]) {
            $errors[] = "Composer installed version mismatch [{$name}] expected {$locked[$name]} got {$installed[$name]}.";
        }
    }
}

/** @param array<string,string> $locked @param array<string,string> $installed @param array<string,mixed> $metrics @param list<string> $errors */
function verifyLaravelRuntimeVersion(
    array $locked,
    array $installed,
    array &$metrics,
    array &$errors,
): void {
    $lockedVersion = ltrim((string) ($locked['laravel/framework'] ?? ''), 'v');
    $installedVersion = ltrim((string) ($installed['laravel/framework'] ?? ''), 'v');
    $metrics['laravel_framework_version'] = $installedVersion !== '' ? $installedVersion : null;

    if ($lockedVersion === '' || $installedVersion === '') {
        $errors[] = 'laravel/framework must exist in both composer.lock and installed Composer metadata.';
        return;
    }
    if (! version_compare($lockedVersion, $installedVersion, '==')) {
        $errors[] = "Installed Laravel version {$installedVersion} does not match composer.lock {$lockedVersion}.";
    }
    if (version_compare($installedVersion, '13.24.0', '<')
        || version_compare($installedVersion, '14.0.0', '>=')) {
        $errors[] = "Installed Laravel {$installedVersion} is outside Nexora certified range >=13.24.0 <14.0.0.";
    }
}

/** @param array<string,mixed> $package @param array<string,mixed> $lock @param array<string,mixed> $installed @param array<string,mixed> $metrics @param list<string> $errors */
function verifyDirectNpmDependencies(
    array $package,
    array $lock,
    array $installed,
    array &$metrics,
    array &$errors,
): void {
    $lockedPackages = (array) ($lock['packages'] ?? []);
    $installedPackages = (array) ($installed['packages'] ?? []);
    $direct = array_unique(array_merge(
        array_keys((array) ($package['dependencies'] ?? [])),
        array_keys((array) ($package['devDependencies'] ?? [])),
    ));

    foreach ($direct as $name) {
        $metrics['npm_direct']++;
        $path = 'node_modules/'.$name;
        $wanted = (string) ($lockedPackages[$path]['version'] ?? '');
        $actual = (string) ($installedPackages[$path]['version'] ?? '');

        if ($wanted === '') {
            $errors[] = "npm direct dependency missing from lock [{$name}].";
        } elseif ($actual === '') {
            $errors[] = "npm direct dependency not installed [{$name}].";
        } elseif ($wanted !== $actual) {
            $errors[] = "npm installed version mismatch [{$name}] expected {$wanted} got {$actual}.";
        }
    }
}
