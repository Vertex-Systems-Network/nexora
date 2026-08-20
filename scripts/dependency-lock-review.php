<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$policy = require $root.'/config/nexora-dependencies.php';

require_once $root.'/scripts/lib/target-composer.php';
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/dependency-toolchain.php';
require_once $root.'/scripts/lib/dependency-candidate-supply-chain.php';

$options = parseReviewOptions($argv);
$evidenceDir = $root.'/storage/app/nexora/dependency-intake';
$attestationPath = $evidenceDir.'/reviewed-locks.json';
$errors = [];
$warnings = [];

$files = [
    'composer_manifest' => 'composer.json',
    'npm_manifest' => 'package.json',
    'composer_lock' => (string) $policy['lockfiles']['composer'],
    'npm_lock' => (string) $policy['lockfiles']['npm'],
];

$hashes = collectDependencyHashes($root, $files, $errors);
$composer = decodeJson($root.'/composer.json', $errors, 'composer.json');
$package = decodeJson($root.'/package.json', $errors, 'package.json');
$composerLock = decodeOptionalJson($root.'/composer.lock', $errors, 'composer.lock');
$npmLock = decodeOptionalJson($root.'/package-lock.json', $errors, 'package-lock.json');

$laravelLockedVersion = validateComposerLock($composerLock, $errors);
$npmSummary = validateNpmLock($package, $npmLock, $errors);
$composerValidation = runComposerLockValidation($root, $warnings, $errors);
$provenance = nexoraDependencyCandidateProvenance($root, $root.'/composer.lock', $root.'/package-lock.json');
if (($provenance['status'] ?? null) !== 'pass') {
    foreach ((array) ($provenance['errors'] ?? []) as $provenanceError) {
        $errors[] = 'Dependency provenance: '.trim((string) $provenanceError);
    }
}
$currentProvenanceFingerprint = (string) ($provenance['fingerprint_sha256'] ?? '');

$current = [
    'composer_manifest_sha256' => $hashes['composer_manifest'] ?? null,
    'package_manifest_sha256' => $hashes['npm_manifest'] ?? null,
    'composer_lock_sha256' => $hashes['composer_lock'] ?? null,
    'package_lock_sha256' => $hashes['npm_lock'] ?? null,
];
$toolchain = nexoraCollectDependencyToolchain($root);
$currentToolchainFingerprint = (string) ($toolchain['fingerprint_sha256'] ?? '');
if (($toolchain['status'] ?? 'fail') !== 'pass') {
    foreach ((array) ($toolchain['errors'] ?? []) as $toolchainError) {
        $errors[] = 'Dependency toolchain: '.trim((string) $toolchainError);
    }
}

if ($options['require_refresh_handoff']) {
    validateRefreshHandoff($root, $evidenceDir, $current, $currentToolchainFingerprint, $currentProvenanceFingerprint, $errors);
}

if ($options['verify']) {
    verifyExistingReview(
        $attestationPath,
        $current,
        $laravelLockedVersion,
        $currentToolchainFingerprint,
        $currentProvenanceFingerprint,
        $errors,
    );
}

if ($options['accept']) {
    validateAcceptanceRequest($options, $composerValidation, $errors);

    if ($errors === []) {
        writeReviewAttestation(
            root: $root,
            path: $attestationPath,
            reviewer: $options['reviewer'],
            current: $current,
            laravelLockedVersion: $laravelLockedVersion,
            composerLock: $composerLock,
            npmLock: $npmLock,
            npmSummary: $npmSummary,
            composerValidation: $composerValidation,
            toolchainFingerprint: $currentToolchainFingerprint,
            provenanceFingerprint: $currentProvenanceFingerprint,
            supplyChainFingerprint: currentRefreshSupplyChainFingerprint($evidenceDir),
        );
    }
}

$result = [
    'schema' => 1,
    'status' => $errors === [] ? 'pass' : 'fail',
    'mode' => $options['accept'] ? 'accept' : ($options['verify'] ? 'verify' : 'inspect'),
    'refresh_handoff_required' => $options['require_refresh_handoff'],
    'hashes' => $current,
    'dependency_toolchain_fingerprint_sha256' => $currentToolchainFingerprint,
    'dependency_provenance_fingerprint_sha256' => $currentProvenanceFingerprint,
    'candidate_supply_chain_fingerprint_sha256' => currentRefreshSupplyChainFingerprint($evidenceDir),
    'laravel_framework_locked_version' => $laravelLockedVersion,
    'composer_validate' => $composerValidation,
    'composer_packages' => packageCount($composerLock),
    'npm_packages' => max(0, count((array) ($npmLock['packages'] ?? [])) - 1),
    'npm_integrity_missing' => count($npmSummary['integrity_missing']),
    'errors' => $errors,
    'warnings' => $warnings,
    'attestation_path' => 'storage/app/nexora/dependency-intake/reviewed-locks.json',
    'next_action' => $errors === []
        ? 'Install the reviewed lockfiles. If Nexora is already installed, run `php artisan nexora:runtime:dependency-status`. '
            .'Use dependency-review-sync when only bootstrap provenance remains, or dependency-reconcile when lock hashes changed.'
        : 'Resolve the first dependency review blocker and inspect the lockfiles again.',
];

writeReviewOutput($result, $options['json']);
exit($errors === [] ? 0 : 1);

/** @return array{accept:bool,verify:bool,json:bool,require_refresh_handoff:bool,reviewer:string,confirm:string} */
function parseReviewOptions(array $arguments): array
{
    $options = [
        'accept' => in_array('--accept', $arguments, true),
        'verify' => in_array('--verify-attestation', $arguments, true),
        'json' => in_array('--json', $arguments, true),
        'require_refresh_handoff' => in_array('--require-refresh-handoff', $arguments, true),
        'reviewer' => '',
        'confirm' => '',
    ];

    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--reviewer=')) {
            $options['reviewer'] = trim(substr($argument, 11));
        }
        if (str_starts_with($argument, '--confirm=')) {
            $options['confirm'] = trim(substr($argument, 10));
        }
    }

    return $options;
}

/** @param array<string,string> $files @param list<string> $errors @return array<string,string|null> */
function collectDependencyHashes(string $root, array $files, array &$errors): array
{
    $hashes = [];

    foreach ($files as $key => $relative) {
        $path = $root.'/'.$relative;
        if (! is_file($path)) {
            $errors[] = "Required dependency artifact missing [{$relative}].";
            $hashes[$key] = null;
            continue;
        }

        $hash = hash_file('sha256', $path);
        $hashes[$key] = is_string($hash) && $hash !== '' ? $hash : null;
    }

    return $hashes;
}

/** @param list<string> $errors @return array<string,mixed> */
function decodeJson(string $path, array &$errors, string $label): array
{
    try {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        $errors[] = "{$label} invalid: {$exception->getMessage()}";
        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

/** @param list<string> $errors @return array<string,mixed> */
function decodeOptionalJson(string $path, array &$errors, string $label): array
{
    return is_file($path) ? decodeJson($path, $errors, $label) : [];
}

/** @param array<string,mixed> $lock @param list<string> $errors */
function validateComposerLock(array $lock, array &$errors): ?string
{
    if ($lock === []) {
        return null;
    }

    if (! is_string($lock['content-hash'] ?? null) || $lock['content-hash'] === '') {
        $errors[] = 'composer.lock is missing content-hash.';
    }
    if (! is_array($lock['packages'] ?? null) || ! is_array($lock['packages-dev'] ?? null)) {
        $errors[] = 'composer.lock package arrays are missing.';
    }

    $version = null;
    foreach ((array) ($lock['packages'] ?? []) as $package) {
        if (($package['name'] ?? null) !== 'laravel/framework') {
            continue;
        }

        $locked = $package['version'] ?? null;
        $version = is_string($locked) ? ltrim(trim($locked), 'v') : null;
        break;
    }

    if ($version === null) {
        $errors[] = 'composer.lock does not contain laravel/framework.';
        return null;
    }

    if (version_compare($version, '13.24.0', '<') || version_compare($version, '14.0.0', '>=')) {
        $errors[] = 'Locked laravel/framework version is outside Nexora certified range >=13.24.0 <14.0.0.';
    }

    return $version;
}

/** @param array<string,mixed> $package @param array<string,mixed> $lock @param list<string> $errors @return array{integrity_missing:list<string>,unsafe_sources:list<string>} */
function validateNpmLock(array $package, array $lock, array &$errors): array
{
    $integrityMissing = [];
    $unsafeSources = [];

    if ($lock === []) {
        return ['integrity_missing' => [], 'integrity_bundled_covered' => [], 'unsafe_sources' => []];
    }

    if ((int) ($lock['lockfileVersion'] ?? 0) < 3) {
        $errors[] = 'package-lock.json must use lockfileVersion >= 3.';
    }

    $rootPackage = (array) ($lock['packages'][''] ?? []);
    foreach (['dependencies', 'devDependencies'] as $bucket) {
        $manifest = (array) ($package[$bucket] ?? []);
        $locked = (array) ($rootPackage[$bucket] ?? []);
        ksort($manifest);
        ksort($locked);
        if ($manifest !== $locked) {
            $errors[] = "package-lock root {$bucket} does not exactly match package.json.";
        }
    }

    $integritySummary = nexoraNpmLockIntegritySummary($lock);
    $integrityMissing = $integritySummary['missing'];

    foreach ((array) ($lock['packages'] ?? []) as $path => $metadata) {
        if ($path === '' || ! is_array($metadata)) {
            continue;
        }
        if (($metadata['link'] ?? false) === true) {
            $unsafeSources[] = "{$path} uses a link package";
            continue;
        }

        $resolved = (string) ($metadata['resolved'] ?? '');
        if ($resolved !== '' && preg_match('/^(?:git\+|git:|file:|workspace:)/i', $resolved) === 1) {
            $unsafeSources[] = "{$path} -> {$resolved}";
        }
    }

    if ($integrityMissing !== []) {
        $errors[] = 'npm lock packages missing integrity metadata: '.implode(', ', array_slice($integrityMissing, 0, 10));
    }
    if ($unsafeSources !== []) {
        $errors[] = 'npm lock contains non-registry/link sources: '.implode(', ', array_slice($unsafeSources, 0, 10));
    }

    return [
        'integrity_missing' => $integrityMissing,
        'integrity_bundled_covered' => $integritySummary['bundled_covered'] ?? [],
        'unsafe_sources' => $unsafeSources,
    ];
}

/** @param list<string> $warnings @param list<string> $errors @return array<string,mixed> */
function runComposerLockValidation(string $root, array &$warnings, array &$errors): array
{
    $tool = nexoraLocateTargetComposer($root);
    if (! $tool['available'] || $tool['command'] === []) {
        $warnings[] = 'Composer unavailable; lock/manifest freshness cannot be accepted on this host.';
        return ['available' => false, 'exit_code' => null, 'output' => null, 'source' => null, 'path' => null];
    }

    $result = nexoraRunTargetCommand(
        array_merge($tool['command'], ['validate', '--strict', '--check-lock', '--no-check-publish']),
        $root,
    );
    $output = trim($result['stdout']."\n".$result['stderr']);
    if ($result['exit_code'] !== 0) {
        $errors[] = 'composer validate --strict --check-lock failed.';
    }

    return [
        'available' => true,
        'exit_code' => $result['exit_code'],
        'output' => $output,
        'source' => $tool['source'],
        'path' => $tool['path'],
    ];
}

/** @param array<string,string|null> $current @param list<string> $errors */
function validateRefreshHandoff(
    string $root,
    string $evidenceDir,
    array $current,
    string $toolchainFingerprint,
    string $provenanceFingerprint,
    array &$errors,
): void
{
    $path = $evidenceDir.'/lock-refresh.json';
    if (! is_file($path)) {
        $errors[] = 'Latest lock-refresh handoff is missing; refresh and review the lock diff first.';
        return;
    }

    $refresh = decodeJson($path, $errors, 'lock-refresh.json');
    $source = nexoraComputeSourceAttestation($root);
    $platform = require $root.'/config/nexora.php';
    $version = (string) ($platform['version'] ?? 'unknown');

    if (($refresh['status'] ?? null) !== 'review-required') {
        $errors[] = 'Latest lock-refresh handoff is not in review-required state.';
    }
    if (($refresh['platform_version'] ?? null) !== $version) {
        $errors[] = 'Lock-refresh handoff platform version mismatch.';
    }
    if (($refresh['source_tree_sha256'] ?? null) !== $source['tree_sha256']) {
        $errors[] = 'Lock-refresh handoff source digest mismatch.';
    }
    if (($refresh['reproducible'] ?? false) !== true) {
        $errors[] = 'Lock-refresh handoff is missing successful double-run reproducibility proof.';
    }
    if (($refresh['toolchain_fingerprint_sha256'] ?? null) !== $toolchainFingerprint) {
        $errors[] = 'Lock-refresh handoff dependency toolchain fingerprint mismatch.';
    }

    $supplyStatus = $refresh['candidate_supply_chain_status'] ?? ($refresh['supply_chain']['status'] ?? null);
    $supplyFingerprint = $refresh['candidate_supply_chain_fingerprint_sha256'] ?? ($refresh['supply_chain']['fingerprint_sha256'] ?? null);
    $savedProvenance = $refresh['candidate_provenance_fingerprint_sha256']
        ?? ($refresh['supply_chain']['workspace_a']['provenance']['fingerprint_sha256'] ?? null);
    if ($supplyStatus !== 'pass' || ! is_string($supplyFingerprint) || preg_match('/^[a-f0-9]{64}$/', $supplyFingerprint) !== 1) {
        $errors[] = 'Lock-refresh handoff candidate supply-chain proof is missing or not PASS.';
    }
    if ($savedProvenance !== $provenanceFingerprint) {
        $errors[] = 'Lock-refresh handoff dependency provenance fingerprint mismatch.';
    }

    foreach (['composer_manifest_sha256', 'package_manifest_sha256'] as $key) {
        if (($refresh['manifest_hashes'][$key] ?? null) !== ($current[$key] ?? null)) {
            $errors[] = "Lock-refresh handoff manifest mismatch [{$key}].";
        }
    }
    foreach (['composer_lock_sha256', 'package_lock_sha256'] as $key) {
        if (($refresh['after'][$key] ?? null) !== ($current[$key] ?? null)) {
            $errors[] = "Lock-refresh handoff lock mismatch [{$key}].";
        }
    }
}

/** @param array<string,string|null> $current @param list<string> $errors */
function verifyExistingReview(
    string $path,
    array $current,
    ?string $laravelVersion,
    string $toolchainFingerprint,
    string $provenanceFingerprint,
    array &$errors,
): void
{
    if (! is_file($path)) {
        $errors[] = 'Reviewed-lock attestation missing.';
        return;
    }

    $saved = decodeJson($path, $errors, 'reviewed-locks.json');
    foreach ($current as $key => $value) {
        if (($saved[$key] ?? null) !== $value) {
            $errors[] = "Reviewed-lock attestation fingerprint mismatch [{$key}].";
        }
    }
    if (($saved['status'] ?? null) !== 'reviewed') {
        $errors[] = 'Reviewed-lock attestation status is not reviewed.';
    }
    if (($saved['laravel_framework_locked_version'] ?? null) !== $laravelVersion) {
        $errors[] = 'Reviewed-lock Laravel framework version no longer matches composer.lock.';
    }
    if (($saved['dependency_toolchain_fingerprint_sha256'] ?? null) !== $toolchainFingerprint) {
        $errors[] = 'Reviewed-lock dependency toolchain fingerprint no longer matches the current toolchain.';
    }
    if (($saved['dependency_provenance_fingerprint_sha256'] ?? null) !== $provenanceFingerprint) {
        $errors[] = 'Reviewed-lock dependency provenance fingerprint no longer matches current lock origins.';
    }
    if (! is_string($saved['candidate_supply_chain_fingerprint_sha256'] ?? null)
        || preg_match('/^[a-f0-9]{64}$/', (string) $saved['candidate_supply_chain_fingerprint_sha256']) !== 1) {
        $errors[] = 'Reviewed-lock candidate supply-chain fingerprint is missing.';
    }
}

/** @param array<string,mixed> $options @param array<string,mixed> $composerValidation @param list<string> $errors */
function validateAcceptanceRequest(array $options, array $composerValidation, array &$errors): void
{
    if ($options['reviewer'] === '' || strlen($options['reviewer']) > 120) {
        $errors[] = '--reviewer=<name> is required and must be <=120 characters.';
    }
    if ($options['confirm'] !== 'REVIEWED') {
        $errors[] = 'Explicit --confirm=REVIEWED is required to accept dependency locks.';
    }
    if (! ($composerValidation['available'] ?? false)) {
        $errors[] = 'Composer must be available before dependency locks can be accepted as reviewed.';
    }
}

/**
 * @param array<string,string|null> $current
 * @param array<string,mixed> $composerLock
 * @param array<string,mixed> $npmLock
 * @param array<string,list<string>> $npmSummary
 * @param array<string,mixed> $composerValidation
 */
function writeReviewAttestation(
    string $root,
    string $path,
    string $reviewer,
    array $current,
    ?string $laravelLockedVersion,
    array $composerLock,
    array $npmLock,
    array $npmSummary,
    array $composerValidation,
    string $toolchainFingerprint,
    string $provenanceFingerprint,
    string $supplyChainFingerprint,
): void {
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create dependency intake evidence directory.');
    }

    $platform = require $root.'/config/nexora.php';
    $payload = array_merge([
        'schema' => 1,
        'status' => 'reviewed',
        'platform_version' => (string) ($platform['version'] ?? 'unknown'),
        'reviewed_at' => gmdate(DATE_ATOM),
        'reviewer' => $reviewer,
        'dependency_toolchain_fingerprint_sha256' => $toolchainFingerprint,
        'dependency_provenance_fingerprint_sha256' => $provenanceFingerprint,
        'candidate_supply_chain_fingerprint_sha256' => $supplyChainFingerprint,
    ], $current, [
        'composer_packages' => packageCount($composerLock),
        'laravel_framework_locked_version' => $laravelLockedVersion,
        'npm_packages' => max(0, count((array) ($npmLock['packages'] ?? [])) - 1),
        'npm_integrity_missing' => count($npmSummary['integrity_missing']),
        'npm_integrity_bundled_covered' => count($npmSummary['integrity_bundled_covered'] ?? []),
        'composer_validate_exit_code' => $composerValidation['exit_code'],
    ]);

    file_put_contents(
        $path,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    );
}

function currentRefreshSupplyChainFingerprint(string $evidenceDir): string
{
    $path = $evidenceDir.'/lock-refresh.json';
    if (! is_file($path)) return '';
    try {
        $refresh = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return '';
    }
    if (! is_array($refresh)) return '';
    return (string) ($refresh['candidate_supply_chain_fingerprint_sha256']
        ?? ($refresh['supply_chain']['fingerprint_sha256'] ?? ''));
}

/** @param array<string,mixed> $lock */
function packageCount(array $lock): int
{
    return count((array) ($lock['packages'] ?? [])) + count((array) ($lock['packages-dev'] ?? []));
}

/** @param array<string,mixed> $result */
function writeReviewOutput(array $result, bool $json): void
{
    if ($json) {
        fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        return;
    }

    fwrite(STDOUT, '[Nexora Dependency Lock Review] '.strtoupper((string) $result['status']).' — mode '.$result['mode'].PHP_EOL);
    foreach ((array) $result['errors'] as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    foreach ((array) $result['warnings'] as $warning) {
        fwrite(STDOUT, " WARNING: {$warning}\n");
    }
    if ($result['mode'] === 'accept' && $result['status'] === 'pass') {
        fwrite(STDOUT, "Reviewed-lock attestation: {$result['attestation_path']}\n");
    }
    fwrite(STDOUT, "Next: {$result['next_action']}\n");
}
