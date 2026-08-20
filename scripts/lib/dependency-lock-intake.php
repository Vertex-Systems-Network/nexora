<?php

declare(strict_types=1);

require_once __DIR__.'/source-attestation.php';
require_once __DIR__.'/target-composer.php';

/** @return array{composer_manifest_sha256:?string,package_manifest_sha256:?string} */
function nexoraDependencyManifestHashes(string $root): array
{
    return [
        'composer_manifest_sha256' => nexoraHashOptionalFile($root.'/composer.json'),
        'package_manifest_sha256' => nexoraHashOptionalFile($root.'/package.json'),
    ];
}

/** @return array{composer_lock_sha256:?string,package_lock_sha256:?string} */
function nexoraDependencyRootLockHashes(string $root): array
{
    return [
        'composer_lock_sha256' => nexoraHashOptionalFile($root.'/composer.lock'),
        'package_lock_sha256' => nexoraHashOptionalFile($root.'/package-lock.json'),
    ];
}

function nexoraHashOptionalFile(string $path): ?string
{
    if (! is_file($path)) {
        return null;
    }

    $hash = hash_file('sha256', $path);

    return is_string($hash) && $hash !== '' ? $hash : null;
}

/**
 * Canonicalize dependency lock JSON for semantic reproducibility checks.
 * Associative key order is non-semantic. Composer package-list ordering is
 * also non-semantic, so packages/packages-dev are sorted by name + version.
 */
function nexoraCanonicalizeDependencyLockValue(mixed $value, ?string $parentKey = null): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    if (! array_is_list($value)) {
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = nexoraCanonicalizeDependencyLockValue($item, (string) $key);
        }
        return $value;
    }

    $items = array_map(
        static fn (mixed $item): mixed => nexoraCanonicalizeDependencyLockValue($item, null),
        $value,
    );

    if (in_array($parentKey, ['packages', 'packages-dev'], true)
        && $items !== []
        && count(array_filter($items, static fn (mixed $item): bool => is_array($item) && is_string($item['name'] ?? null))) === count($items)) {
        usort($items, static function (array $left, array $right): int {
            $name = strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            return $name !== 0 ? $name : strcmp((string) ($left['version'] ?? ''), (string) ($right['version'] ?? ''));
        });
    }

    return $items;
}

function nexoraDependencyLockSemanticSha(string $path): ?string
{
    if (! is_file($path)) {
        return null;
    }
    try {
        $decoded = json_decode((string) file_get_contents($path), true, 2048, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }
    if (! is_array($decoded)) {
        return null;
    }
    $canonical = nexoraCanonicalizeDependencyLockValue($decoded);
    return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

/** @return array{composer_lock_semantic_sha256:?string,package_lock_semantic_sha256:?string} */
function nexoraDependencyLockSemanticDigests(string $composerLockPath, string $packageLockPath): array
{
    return [
        'composer_lock_semantic_sha256' => nexoraDependencyLockSemanticSha($composerLockPath),
        'package_lock_semantic_sha256' => nexoraDependencyLockSemanticSha($packageLockPath),
    ];
}


/**
 * Resolve integrity coverage for one npm package-lock v3 entry.
 *
 * Direct registry packages must carry their own SRI. `inBundle` children may
 * omit `resolved`/`integrity` only when an ancestor bundle owner explicitly
 * lists the direct bundled package and that owner itself has a resolved URL
 * plus SRI integrity.
 *
 * @param array<string,mixed> $packages
 * @param array<string,mixed> $metadata
 * @return array{status:string,mode:string,owner_path:?string,owner_integrity:?string,error:?string}
 */
function nexoraNpmPackageIntegrityCoverage(array $packages, string $path, array $metadata): array
{
    $integrity = is_string($metadata['integrity'] ?? null) ? trim((string) $metadata['integrity']) : '';
    $resolved = is_string($metadata['resolved'] ?? null) ? trim((string) $metadata['resolved']) : '';

    if ($integrity !== '') {
        return [
            'status' => 'pass',
            'mode' => 'direct',
            'owner_path' => null,
            'owner_integrity' => $integrity,
            'error' => null,
        ];
    }

    if (($metadata['inBundle'] ?? false) !== true) {
        return [
            'status' => 'fail',
            'mode' => 'missing',
            'owner_path' => null,
            'owner_integrity' => null,
            'error' => $resolved !== ''
                ? 'external package is missing integrity metadata'
                : 'package has neither direct integrity nor verified bundle coverage',
        ];
    }

    $cursor = $path;
    while (($separator = strrpos($cursor, '/node_modules/')) !== false) {
        $ownerPath = substr($cursor, 0, $separator);
        $owner = $packages[$ownerPath] ?? null;
        if (! is_array($owner)) {
            $cursor = $ownerPath;
            continue;
        }

        // Keep climbing through nested bundled packages until the real bundle
        // owner (the registry package carrying the tarball SRI) is reached.
        if (($owner['inBundle'] ?? false) === true) {
            $cursor = $ownerPath;
            continue;
        }

        $ownerIntegrity = is_string($owner['integrity'] ?? null) ? trim((string) $owner['integrity']) : '';
        $ownerResolved = is_string($owner['resolved'] ?? null) ? trim((string) $owner['resolved']) : '';
        if ($ownerIntegrity === '' || $ownerResolved === '') {
            return [
                'status' => 'fail',
                'mode' => 'bundle-uncovered',
                'owner_path' => $ownerPath,
                'owner_integrity' => $ownerIntegrity !== '' ? $ownerIntegrity : null,
                'error' => 'bundle owner is missing resolved URL or integrity metadata',
            ];
        }

        $relative = substr($path, strlen($ownerPath.'/node_modules/'));
        $parts = explode('/', $relative);
        $directName = str_starts_with($relative, '@') && count($parts) >= 2
            ? $parts[0].'/'.$parts[1]
            : $parts[0];
        $bundled = (array) ($owner['bundleDependencies'] ?? $owner['bundledDependencies'] ?? []);
        if (! in_array($directName, $bundled, true)) {
            return [
                'status' => 'fail',
                'mode' => 'bundle-unlisted',
                'owner_path' => $ownerPath,
                'owner_integrity' => $ownerIntegrity,
                'error' => 'bundle owner does not list the direct bundled package',
            ];
        }

        return [
            'status' => 'pass',
            'mode' => 'bundled',
            'owner_path' => $ownerPath,
            'owner_integrity' => $ownerIntegrity,
            'error' => null,
        ];
    }

    return [
        'status' => 'fail',
        'mode' => 'bundle-owner-missing',
        'owner_path' => null,
        'owner_integrity' => null,
        'error' => 'inBundle package has no verifiable bundle owner',
    ];
}

/**
 * @return array{missing:list<string>,bundled_covered:array<string,string>,direct_covered:int}
 */
function nexoraNpmLockIntegritySummary(array $npmLock): array
{
    $packages = (array) ($npmLock['packages'] ?? []);
    $missing = [];
    $bundled = [];
    $direct = 0;

    foreach ($packages as $path => $metadata) {
        if ($path === '' || ! is_array($metadata) || ($metadata['link'] ?? false) === true) {
            continue;
        }
        $coverage = nexoraNpmPackageIntegrityCoverage($packages, (string) $path, $metadata);
        if (($coverage['status'] ?? null) !== 'pass') {
            $missing[] = (string) $path;
            continue;
        }
        if (($coverage['mode'] ?? null) === 'bundled') {
            $bundled[(string) $path] = (string) ($coverage['owner_path'] ?? '');
        } else {
            $direct++;
        }
    }

    ksort($bundled, SORT_STRING);
    sort($missing, SORT_STRING);
    return ['missing' => $missing, 'bundled_covered' => $bundled, 'direct_covered' => $direct];
}

/**
 * @return array{
 *   errors:list<string>,warnings:list<string>,composer_packages:int,npm_packages:int,
 *   laravel_framework_locked_version:?string,npm_integrity_missing:int,npm_integrity_bundled_covered:int,npm_unsafe_sources:int
 * }
 */
function nexoraValidateDependencyLockPair(
    string $root,
    string $composerLockPath,
    string $packageLockPath,
    bool $runComposerValidate = false,
): array {
    $errors = [];
    $warnings = [];
    $composerPackages = 0;
    $npmPackages = 0;
    $laravelFrameworkLockedVersion = null;
    $npmIntegrityMissing = [];
    $npmUnsafeSources = [];

    $composerManifest = nexoraDecodeDependencyJson($root.'/composer.json', 'composer.json', $errors);
    $packageManifest = nexoraDecodeDependencyJson($root.'/package.json', 'package.json', $errors);
    $composerLock = nexoraDecodeDependencyJson($composerLockPath, 'composer.lock candidate', $errors);
    $npmLock = nexoraDecodeDependencyJson($packageLockPath, 'package-lock.json candidate', $errors);

    if ($composerLock !== []) {
        if (! is_string($composerLock['content-hash'] ?? null) || trim((string) $composerLock['content-hash']) === '') {
            $errors[] = 'composer.lock candidate is missing content-hash.';
        }
        if (! is_array($composerLock['packages'] ?? null) || ! is_array($composerLock['packages-dev'] ?? null)) {
            $errors[] = 'composer.lock candidate package arrays are missing.';
        }

        $composerPackages = count((array) ($composerLock['packages'] ?? []))
            + count((array) ($composerLock['packages-dev'] ?? []));
        foreach ((array) ($composerLock['packages'] ?? []) as $package) {
            if (! is_array($package) || ($package['name'] ?? null) !== 'laravel/framework') {
                continue;
            }
            $locked = $package['version'] ?? null;
            $laravelFrameworkLockedVersion = is_string($locked)
                ? ltrim(trim($locked), 'v')
                : null;
            break;
        }

        if ($laravelFrameworkLockedVersion === null) {
            $errors[] = 'composer.lock candidate does not contain laravel/framework.';
        } elseif (version_compare($laravelFrameworkLockedVersion, '13.24.0', '<')
            || version_compare($laravelFrameworkLockedVersion, '14.0.0', '>=')) {
            $errors[] = 'composer.lock candidate Laravel version is outside >=13.24.0 <14.0.0.';
        }
    }

    if ($npmLock !== []) {
        if ((int) ($npmLock['lockfileVersion'] ?? 0) < 3) {
            $errors[] = 'package-lock.json candidate must use lockfileVersion >= 3.';
        }

        $rootPackage = (array) ($npmLock['packages'][''] ?? []);
        foreach (['dependencies', 'devDependencies'] as $bucket) {
            $manifest = (array) ($packageManifest[$bucket] ?? []);
            $locked = (array) ($rootPackage[$bucket] ?? []);
            ksort($manifest);
            ksort($locked);
            if ($manifest !== $locked) {
                $errors[] = "package-lock.json candidate root {$bucket} does not exactly match package.json.";
            }
        }

        $integritySummary = nexoraNpmLockIntegritySummary($npmLock);
        $npmIntegrityMissing = $integritySummary['missing'];

        foreach ((array) ($npmLock['packages'] ?? []) as $path => $metadata) {
            if ($path === '' || ! is_array($metadata)) {
                continue;
            }
            if (($metadata['link'] ?? false) === true) {
                $npmUnsafeSources[] = (string) $path.' uses a link package';
                continue;
            }

            $resolved = (string) ($metadata['resolved'] ?? '');
            if ($resolved !== '' && preg_match('/^(?:git\+|git:|file:|workspace:)/i', $resolved) === 1) {
                $npmUnsafeSources[] = (string) $path.' -> '.$resolved;
            }
        }

        if ($npmIntegrityMissing !== []) {
            $errors[] = 'package-lock.json candidate packages missing integrity metadata: '
                .implode(', ', array_slice($npmIntegrityMissing, 0, 10));
        }
        if ($npmUnsafeSources !== []) {
            $errors[] = 'package-lock.json candidate contains non-registry/link sources: '
                .implode(', ', array_slice($npmUnsafeSources, 0, 10));
        }
        $npmPackages = max(0, count((array) ($npmLock['packages'] ?? [])) - 1);
    }

    if ($composerManifest === []) {
        $errors[] = 'composer.json could not be read for candidate validation.';
    }

    if ($runComposerValidate) {
        $composer = nexoraLocateTargetComposer($root);
        if (! $composer['available'] || $composer['command'] === []) {
            $errors[] = 'Composer is unavailable for strict candidate validation.';
        } else {
            $workspace = dirname($composerLockPath);
            $environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
            $result = nexoraRunTargetCommand(
                array_merge($composer['command'], ['validate', '--strict', '--check-lock', '--no-check-publish']),
                $workspace,
                $environment,
            );
            if ($result['exit_code'] !== 0) {
                $errors[] = 'composer validate --strict --check-lock failed in the isolated lock workspace.';
                $detail = trim($result['stderr'] !== '' ? $result['stderr'] : $result['stdout']);
                if ($detail !== '') {
                    $warnings[] = substr($detail, 0, 1000);
                }
            }
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
        'composer_packages' => $composerPackages,
        'npm_packages' => $npmPackages,
        'laravel_framework_locked_version' => $laravelFrameworkLockedVersion,
        'npm_integrity_missing' => count($npmIntegrityMissing),
        'npm_integrity_bundled_covered' => isset($integritySummary) ? count($integritySummary['bundled_covered']) : 0,
        'npm_unsafe_sources' => count($npmUnsafeSources),
    ];
}

/** @param list<string> $errors @return array<string,mixed> */
function nexoraDecodeDependencyJson(string $path, string $label, array &$errors): array
{
    if (! is_file($path)) {
        $errors[] = "Required {$label} missing [{$path}].";
        return [];
    }

    try {
        $decoded = json_decode((string) file_get_contents($path), true, 1024, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        $errors[] = "{$label} invalid: {$exception->getMessage()}";
        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

/** @return array<string,string> package name => version */
function nexoraComposerLockVersions(string $path): array
{
    if (! is_file($path)) {
        return [];
    }
    try {
        $lock = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return [];
    }

    $versions = [];
    foreach (['packages', 'packages-dev'] as $bucket) {
        foreach ((array) ($lock[$bucket] ?? []) as $package) {
            if (! is_array($package) || ! is_string($package['name'] ?? null)) {
                continue;
            }
            $versions[(string) $package['name']] = (string) ($package['version'] ?? 'unknown');
        }
    }
    ksort($versions, SORT_STRING);

    return $versions;
}

/** @return array<string,string> package path/name => version */
function nexoraNpmLockVersions(string $path): array
{
    if (! is_file($path)) {
        return [];
    }
    try {
        $lock = json_decode((string) file_get_contents($path), true, 1024, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return [];
    }

    $versions = [];
    foreach ((array) ($lock['packages'] ?? []) as $packagePath => $metadata) {
        if ($packagePath === '' || ! is_array($metadata)) {
            continue;
        }
        $name = str_starts_with((string) $packagePath, 'node_modules/')
            ? substr((string) $packagePath, strlen('node_modules/'))
            : (string) $packagePath;
        $versions[$name] = (string) ($metadata['version'] ?? 'unknown');
    }
    ksort($versions, SORT_STRING);

    return $versions;
}

/** @return array{added:array<string,string>,removed:array<string,string>,changed:array<string,array{before:string,after:string}>} */
function nexoraDependencyVersionDiff(array $before, array $after): array
{
    $added = [];
    $removed = [];
    $changed = [];

    foreach ($after as $name => $version) {
        if (! array_key_exists($name, $before)) {
            $added[$name] = (string) $version;
            continue;
        }
        if ((string) $before[$name] !== (string) $version) {
            $changed[$name] = ['before' => (string) $before[$name], 'after' => (string) $version];
        }
    }
    foreach ($before as $name => $version) {
        if (! array_key_exists($name, $after)) {
            $removed[$name] = (string) $version;
        }
    }
    ksort($added, SORT_STRING);
    ksort($removed, SORT_STRING);
    ksort($changed, SORT_STRING);

    return ['added' => $added, 'removed' => $removed, 'changed' => $changed];
}

function nexoraWriteFileReplace(string $path, string $contents): void
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create directory [{$directory}].");
    }

    $temp = $path.'.nexora-new-'.bin2hex(random_bytes(6));
    if (file_put_contents($temp, $contents, LOCK_EX) === false) {
        throw new RuntimeException("Unable to stage replacement [{$path}].");
    }

    if (is_file($path) && ! @unlink($path)) {
        @unlink($temp);
        throw new RuntimeException("Unable to replace existing file [{$path}].");
    }
    if (! @rename($temp, $path)) {
        @unlink($temp);
        throw new RuntimeException("Unable to publish replacement [{$path}].");
    }
}

/** @param array{exists:bool,contents:?string} $snapshot */
function nexoraRestoreFileSnapshot(string $path, array $snapshot): void
{
    if (($snapshot['exists'] ?? false) !== true) {
        if (is_file($path) && ! @unlink($path)) {
            throw new RuntimeException("Unable to remove promoted file during rollback [{$path}].");
        }
        return;
    }

    nexoraWriteFileReplace($path, (string) ($snapshot['contents'] ?? ''));
}

/** @return array{exists:bool,contents:?string,sha256:?string} */
function nexoraCaptureFileSnapshot(string $path): array
{
    if (! is_file($path)) {
        return ['exists' => false, 'contents' => null, 'sha256' => null];
    }

    $contents = file_get_contents($path);
    if (! is_string($contents)) {
        throw new RuntimeException("Unable to read snapshot [{$path}].");
    }

    return [
        'exists' => true,
        'contents' => $contents,
        'sha256' => hash('sha256', $contents),
    ];
}
