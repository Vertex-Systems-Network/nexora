<?php

declare(strict_types=1);

/**
 * External, version-pinned repair utility for the already-installed Nexora rc.93
 * target affected by premature post-install runtime identity sealing.
 *
 * This file is intentionally self-contained. It boots the TARGET application's
 * own vendor/autoload.php and bootstrap/app.php; it never loads or copies the
 * current checkout's application source into the target.
 */

const NEXORA_RC93_REPAIR_EXPECTED_VERSION = '1.0.0-rc.93';
const NEXORA_RC93_REPAIR_CONFIRMATION = 'REPAIR-RC93';
const NEXORA_RC93_REPAIR_ALLOWED_MISMATCHES = [
    'environment',
    'activation',
    'service',
    'process',
];

/** @param array<string,mixed> $payload */
function nexoraRc93RepairEmit(array $payload, int $exitCode = 0): never
{
    $payload['schema'] = 1;
    $payload['tool'] = 'rc93-post-install-identity-repair';
    $payload['expected_target_version'] = NEXORA_RC93_REPAIR_EXPECTED_VERSION;
    fwrite($exitCode === 0 ? STDOUT : STDERR, json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL);
    exit($exitCode);
}

/** @param array<string,mixed> $context */
function nexoraRc93RepairFail(string $message, array $context = []): never
{
    nexoraRc93RepairEmit([
        'status' => 'fail',
        'message' => $message,
        ...$context,
    ], 1);
}

function nexoraRc93RepairNormalizeFilesystemPath(string $path): string
{
    $normalized = rtrim(str_replace('\\', '/', $path), '/');
    if ($normalized === '') {
        $normalized = '/';
    }

    return PHP_OS_FAMILY === 'Windows' ? strtolower($normalized) : $normalized;
}

function nexoraRc93RepairTargetFile(string $target, string $relative): ?string
{
    $resolvedTarget = realpath($target);
    if (! is_string($resolvedTarget) || ! is_dir($resolvedTarget)) {
        return null;
    }

    $relative = trim(str_replace('\\', '/', $relative), '/');
    $segments = array_values(array_filter(explode('/', $relative), static fn (string $segment): bool => $segment !== ''));
    if ($segments === [] || in_array('.', $segments, true) || in_array('..', $segments, true)) {
        return null;
    }

    $targetPath = nexoraRc93RepairNormalizeFilesystemPath($resolvedTarget);
    $targetPrefix = $targetPath === '/' ? '/' : $targetPath.'/';
    $current = $resolvedTarget;
    $lastIndex = count($segments) - 1;

    foreach ($segments as $index => $segment) {
        $candidate = $current.DIRECTORY_SEPARATOR.$segment;
        if (is_link($candidate)) {
            return null;
        }

        $isLast = $index === $lastIndex;
        if ($isLast ? ! is_file($candidate) : ! is_dir($candidate)) {
            return null;
        }

        $resolved = realpath($candidate);
        if (! is_string($resolved)) {
            return null;
        }

        $candidatePath = nexoraRc93RepairNormalizeFilesystemPath($candidate);
        $resolvedPath = nexoraRc93RepairNormalizeFilesystemPath($resolved);
        if (! hash_equals($candidatePath, $resolvedPath) || ! str_starts_with($resolvedPath, $targetPrefix)) {
            return null;
        }

        $current = $resolved;
    }

    return is_file($current) ? $current : null;
}

/** @param list<string> $segments */
function nexoraRc93RepairTargetDirectory(string $target, array $segments, bool $create): ?string
{
    $resolvedTarget = realpath($target);
    if (! is_string($resolvedTarget) || ! is_dir($resolvedTarget) || $segments === []) {
        return null;
    }

    $targetPath = nexoraRc93RepairNormalizeFilesystemPath($resolvedTarget);
    $targetPrefix = $targetPath === '/' ? '/' : $targetPath.'/';
    $current = $resolvedTarget;

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..' || str_contains($segment, '/') || str_contains($segment, '\\')) {
            return null;
        }

        $candidate = $current.DIRECTORY_SEPARATOR.$segment;
        if (is_link($candidate)) {
            return null;
        }

        if (file_exists($candidate)) {
            if (! is_dir($candidate)) {
                return null;
            }
        } else {
            if (! $create || (! @mkdir($candidate, 0700) && ! is_dir($candidate))) {
                return null;
            }
            @chmod($candidate, 0700);
        }

        if (is_link($candidate)) {
            return null;
        }
        $resolved = realpath($candidate);
        if (! is_string($resolved) || ! is_dir($resolved)) {
            return null;
        }

        $candidatePath = nexoraRc93RepairNormalizeFilesystemPath($candidate);
        $resolvedPath = nexoraRc93RepairNormalizeFilesystemPath($resolved);
        if (! hash_equals($candidatePath, $resolvedPath) || ! str_starts_with($resolvedPath, $targetPrefix)) {
            return null;
        }

        $current = $resolved;
    }

    return $current;
}

function nexoraRc93RepairContainedExistingFile(string $target, string $path): ?string
{
    if (is_link($path) || ! is_file($path)) {
        return null;
    }

    $resolvedTarget = realpath($target);
    $resolved = realpath($path);
    if (! is_string($resolvedTarget) || ! is_string($resolved) || ! is_file($resolved)) {
        return null;
    }

    $targetPath = nexoraRc93RepairNormalizeFilesystemPath($resolvedTarget);
    $targetPrefix = $targetPath === '/' ? '/' : $targetPath.'/';
    $pathNormalized = nexoraRc93RepairNormalizeFilesystemPath($path);
    $resolvedPath = nexoraRc93RepairNormalizeFilesystemPath($resolved);
    if (! hash_equals($pathNormalized, $resolvedPath) || ! str_starts_with($resolvedPath, $targetPrefix)) {
        return null;
    }

    return $resolved;
}

function nexoraRc93RepairHash(string $value): string
{
    return hash('sha256', $value);
}

function nexoraRc93RepairHashFile(string $path): string
{
    $hash = @hash_file('sha256', $path);
    if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
        nexoraRc93RepairFail('Unable to calculate a required SHA-256 digest.', ['path' => $path]);
    }

    return $hash;
}

function nexoraRc93RepairRequireHash(mixed $value, string $field): string
{
    $hash = strtolower(trim((string) $value));
    if (preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
        nexoraRc93RepairFail('A required runtime identity value is not a valid SHA-256 fingerprint.', [
            'field' => $field,
        ]);
    }

    return $hash;
}

/** @return list<string> */
function nexoraRc93RepairMismatches(array $assessment): array
{
    $rows = [];
    foreach ((array) ($assessment['mismatches'] ?? []) as $mismatch) {
        $name = strtolower(trim((string) $mismatch));
        if ($name !== '') {
            $rows[] = $name;
        }
    }
    $rows = array_values(array_unique($rows));
    sort($rows, SORT_STRING);

    return $rows;
}

function nexoraRc93RepairRequireMethods(object $object, string $class, array $methods): void
{
    foreach ($methods as $method) {
        if (! method_exists($object, $method)) {
            nexoraRc93RepairFail('The rc.93 target does not expose a required bounded-repair API. No mutation was performed.', [
                'class' => $class,
                'missing_method' => $method,
            ]);
        }
    }
}

function nexoraRc93RepairForgetRuntimeMemoization(
    object $deployment,
    object $environment,
    object $activation,
    object $services,
    object $processes,
): void {
    foreach ([
        [$deployment, 'forgetMemoizedIdentity'],
        [$environment, 'forgetMemoizedIdentity'],
        [$services, 'forgetMemoizedIdentity'],
        [$processes, 'forgetMemoizedPolicy'],
    ] as [$object, $method]) {
        if (method_exists($object, $method)) {
            $object->{$method}();
        }
    }

    if (method_exists($activation, 'adoptCurrentEpochForProcess')) {
        $activation->adoptCurrentEpochForProcess();
    }
}

function nexoraRc93RepairUsage(): string
{
    return <<<'TEXT'
Nexora rc.93 Post-Install Identity Repair Pack

Dry-run (default):
  php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora"

Apply after reviewing the dry-run:
  php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora" --apply --confirm=REPAIR-RC93

The tool refuses every target version except 1.0.0-rc.93, refuses mismatches
outside environment/activation/service/process, verifies source/deployment identity,
backs up the sealed installation lock, and restores it if convergence fails.
TEXT;
}

$options = getopt('', ['target:', 'apply', 'confirm:', 'help']);
if ($options === false) {
    nexoraRc93RepairFail('Unable to parse command-line options.');
}

if (array_key_exists('help', $options)) {
    fwrite(STDOUT, nexoraRc93RepairUsage().PHP_EOL);
    exit(0);
}

$apply = array_key_exists('apply', $options);
$confirmation = trim((string) ($options['confirm'] ?? ''));
if ($apply && ! hash_equals(NEXORA_RC93_REPAIR_CONFIRMATION, $confirmation)) {
    nexoraRc93RepairFail('Apply mode requires the exact confirmation token.', [
        'required_confirmation' => NEXORA_RC93_REPAIR_CONFIRMATION,
    ]);
}
if (! $apply && $confirmation !== '') {
    nexoraRc93RepairFail('A confirmation token is only valid together with --apply.');
}

$targetInput = trim((string) ($options['target'] ?? ''));
if ($targetInput === '') {
    nexoraRc93RepairFail('An explicit --target path is required.');
}
if (is_link($targetInput)) {
    nexoraRc93RepairFail('Refusing a symbolic-link target path.');
}

$target = realpath($targetInput);
if (! is_string($target) || ! is_dir($target)) {
    nexoraRc93RepairFail('Target path does not resolve to an existing directory.', [
        'target' => $targetInput,
    ]);
}

$runtimeFiles = [];
foreach (['artisan', 'vendor/autoload.php', 'bootstrap/app.php'] as $relative) {
    $path = nexoraRc93RepairTargetFile($target, $relative);
    if ($path === null) {
        nexoraRc93RepairFail('Target is missing or redirects a required regular Nexora runtime file.', [
            'missing_or_unsafe' => $relative,
            'target' => $target,
        ]);
    }
    $runtimeFiles[$relative] = $path;
}

$previousCwd = getcwd();
if (! @chdir($target)) {
    nexoraRc93RepairFail('Unable to enter the target directory.', ['target' => $target]);
}

try {
    require $runtimeFiles['vendor/autoload.php'];
    $app = require $runtimeFiles['bootstrap/app.php'];

    if (! is_object($app) || ! method_exists($app, 'make')) {
        nexoraRc93RepairFail('Target bootstrap did not return a Laravel application container.');
    }

    $kernelClass = 'Illuminate\\Contracts\\Console\\Kernel';
    if (! interface_exists($kernelClass)) {
        nexoraRc93RepairFail('Target Laravel console kernel contract is unavailable.');
    }
    $kernel = $app->make($kernelClass);
    if (! is_object($kernel) || ! method_exists($kernel, 'bootstrap')) {
        nexoraRc93RepairFail('Target Laravel console kernel cannot be bootstrapped.');
    }
    $kernel->bootstrap();

    $classMap = [
        'installation' => 'App\\Nexora\\Installation\\InstallationState',
        'source' => 'App\\Nexora\\Installation\\SourceActivationIdentity',
        'deployment' => 'App\\Nexora\\Cloud\\Services\\RuntimeDeploymentIdentity',
        'environment' => 'App\\Nexora\\Cloud\\Services\\RuntimeEnvironmentIdentity',
        'activation' => 'App\\Nexora\\Cloud\\Services\\RuntimeActivationIdentity',
        'services' => 'App\\Nexora\\Cloud\\Services\\RuntimeServiceDataPlaneIdentity',
        'processes' => 'App\\Nexora\\Cloud\\Services\\RuntimeProcessPlane',
        'guard' => 'App\\Nexora\\Cloud\\Services\\RuntimeVersionGuard',
        'atomic' => 'App\\Nexora\\Foundation\\Filesystem\\AtomicFileWriter',
    ];
    foreach ($classMap as $role => $class) {
        if (! class_exists($class)) {
            nexoraRc93RepairFail('The rc.93 target is missing a required runtime class. No mutation was performed.', [
                'role' => $role,
                'class' => $class,
            ]);
        }
    }

    $installation = $app->make($classMap['installation']);
    $source = $app->make($classMap['source']);
    $deployment = $app->make($classMap['deployment']);
    $environment = $app->make($classMap['environment']);
    $activation = $app->make($classMap['activation']);
    $services = $app->make($classMap['services']);
    $processes = $app->make($classMap['processes']);
    $guard = $app->make($classMap['guard']);
    $atomic = $app->make($classMap['atomic']);

    foreach ([$installation, $source, $deployment, $environment, $activation, $services, $processes, $guard, $atomic] as $object) {
        if (! is_object($object)) {
            nexoraRc93RepairFail('Target container returned an invalid bounded-repair dependency.');
        }
    }

    nexoraRc93RepairRequireMethods($installation, $classMap['installation'], ['inspect', 'metadata', 'lockPath', 'updateMetadata']);
    nexoraRc93RepairRequireMethods($source, $classMap['source'], ['inspect']);
    nexoraRc93RepairRequireMethods($deployment, $classMap['deployment'], ['deepVerify']);
    nexoraRc93RepairRequireMethods($environment, $classMap['environment'], ['current']);
    nexoraRc93RepairRequireMethods($activation, $classMap['activation'], ['current']);
    nexoraRc93RepairRequireMethods($services, $classMap['services'], ['current']);
    nexoraRc93RepairRequireMethods($processes, $classMap['processes'], ['policy']);
    nexoraRc93RepairRequireMethods($guard, $classMap['guard'], ['assess']);
    nexoraRc93RepairRequireMethods($atomic, $classMap['atomic'], ['write']);

    $runningVersion = trim((string) config('nexora.version', ''));
    $inspection = $installation->inspect();
    if (! is_array($inspection) || ($inspection['valid'] ?? false) !== true) {
        nexoraRc93RepairFail('Installed-state lock is missing or invalid. No mutation was performed.', [
            'installation_status' => is_array($inspection) ? ($inspection['status'] ?? 'invalid') : 'invalid',
        ]);
    }

    $metadata = $installation->metadata();
    if (! is_array($metadata)) {
        nexoraRc93RepairFail('Installed metadata is unavailable. No mutation was performed.');
    }
    $installedVersion = trim((string) ($metadata['version'] ?? ''));
    if (! hash_equals(NEXORA_RC93_REPAIR_EXPECTED_VERSION, $runningVersion)
        || ! hash_equals(NEXORA_RC93_REPAIR_EXPECTED_VERSION, $installedVersion)) {
        nexoraRc93RepairFail('This repair pack is pinned to an installed rc.93 target and refuses every other version.', [
            'running_version' => $runningVersion,
            'installed_version' => $installedVersion,
        ]);
    }

    $lockPath = $installation->lockPath();
    $containedLockPath = is_string($lockPath) ? nexoraRc93RepairContainedExistingFile($target, $lockPath) : null;
    if ($containedLockPath === null) {
        nexoraRc93RepairFail('Installed-state lock path is missing, redirected, or outside the exact target. No mutation was performed.', [
            'lock_path' => is_string($lockPath) ? $lockPath : null,
        ]);
    }
    $lockPath = $containedLockPath;
    $lockBytes = @file_get_contents($lockPath);
    if (! is_string($lockBytes) || $lockBytes === '') {
        nexoraRc93RepairFail('Unable to read the sealed installation lock. No mutation was performed.');
    }
    $lockShaBefore = nexoraRc93RepairHash($lockBytes);

    nexoraRc93RepairForgetRuntimeMemoization($deployment, $environment, $activation, $services, $processes);

    $sourceState = $source->inspect();
    if (! is_array($sourceState) || ($sourceState['status'] ?? 'fail') !== 'pass') {
        nexoraRc93RepairFail('Immutable source activation preflight is not PASS. No mutation was performed.', [
            'source_status' => is_array($sourceState) ? ($sourceState['status'] ?? 'fail') : 'fail',
            'source_errors' => is_array($sourceState) ? array_values((array) ($sourceState['errors'] ?? [])) : [],
        ]);
    }

    $deploymentState = $deployment->deepVerify();
    if (! is_array($deploymentState) || ($deploymentState['ok'] ?? false) !== true) {
        nexoraRc93RepairFail('Immutable deployment/source preflight is not PASS. No mutation was performed.', [
            'deployment_errors' => is_array($deploymentState) ? array_values((array) ($deploymentState['errors'] ?? [])) : [],
        ]);
    }

    $before = $guard->assess();
    if (! is_array($before)) {
        nexoraRc93RepairFail('Runtime compatibility assessment returned an invalid payload. No mutation was performed.');
    }
    $mismatches = nexoraRc93RepairMismatches($before);
    $unexpected = array_values(array_diff($mismatches, NEXORA_RC93_REPAIR_ALLOWED_MISMATCHES));
    if ($unexpected !== []) {
        nexoraRc93RepairFail('Repair refused because an immutable or unrelated runtime identity plane is mismatched.', [
            'mismatches' => $mismatches,
            'unexpected_mismatches' => $unexpected,
            'allowed_mismatches' => NEXORA_RC93_REPAIR_ALLOWED_MISMATCHES,
        ]);
    }

    if ($mismatches === []) {
        nexoraRc93RepairEmit([
            'status' => 'pass',
            'mode' => 'no-op',
            'message' => 'The rc.93 target is already compatible; no repair was needed.',
            'target' => $target,
            'compatibility_mode' => $before['mode'] ?? null,
            'mismatches' => [],
            'mutation_performed' => false,
        ]);
    }

    $environmentState = $environment->current();
    $activationState = $activation->current();
    $serviceState = $services->current(true);
    $processState = $processes->policy();

    if (! is_array($environmentState) || ! is_array($activationState)
        || ! is_array($serviceState) || ! is_array($processState)) {
        nexoraRc93RepairFail('One or more target runtime identity providers returned an invalid payload. No mutation was performed.');
    }
    if (($serviceState['status'] ?? 'fail') !== 'pass') {
        nexoraRc93RepairFail('Runtime service data-plane deep probe is not PASS. No mutation was performed.', [
            'service_status' => $serviceState['status'] ?? 'fail',
        ]);
    }
    if (($processState['status'] ?? 'fail') !== 'pass') {
        nexoraRc93RepairFail('Runtime process policy is not PASS. No mutation was performed.', [
            'process_status' => $processState['status'] ?? 'fail',
        ]);
    }

    $updates = [
        'runtime_environment_fingerprint' => nexoraRc93RepairRequireHash($environmentState['fingerprint'] ?? null, 'runtime_environment_fingerprint'),
        'key_fingerprint' => nexoraRc93RepairRequireHash($environmentState['active_key_fingerprint'] ?? null, 'key_fingerprint'),
        'activation_epoch' => nexoraRc93RepairRequireHash($activationState['activation_epoch'] ?? null, 'activation_epoch'),
        'runtime_activation_fingerprint' => nexoraRc93RepairRequireHash($activationState['activation_fingerprint'] ?? null, 'runtime_activation_fingerprint'),
        'runtime_activation_cache_sha256' => nexoraRc93RepairRequireHash($activationState['framework_cache']['snapshot_sha256'] ?? null, 'runtime_activation_cache_sha256'),
        'runtime_activated_at' => gmdate(DATE_ATOM),
        'runtime_service_fingerprint' => nexoraRc93RepairRequireHash($serviceState['fingerprint'] ?? null, 'runtime_service_fingerprint'),
        'service_deep_probe_sha256' => nexoraRc93RepairRequireHash($serviceState['deep']['deep_sha256'] ?? null, 'service_deep_probe_sha256'),
        'cache_service_store' => (string) ($serviceState['materials']['cache']['store'] ?? ''),
        'queue_service_connection' => (string) ($serviceState['materials']['queue']['connection'] ?? ''),
        'mail_service_default' => (string) ($serviceState['materials']['mail']['default'] ?? ''),
        'runtime_process_fingerprint' => nexoraRc93RepairRequireHash($processState['fingerprint'] ?? null, 'runtime_process_fingerprint'),
        'process_strict_certification_status' => (string) ($processState['status'] ?? 'fail'),
    ];

    if (! $apply) {
        nexoraRc93RepairEmit([
            'status' => 'pass',
            'mode' => 'dry-run',
            'message' => 'Preflight passed. No mutation was performed. Review this plan, then rerun with --apply --confirm=REPAIR-RC93.',
            'target' => $target,
            'running_version' => $runningVersion,
            'installed_version' => $installedVersion,
            'compatibility_mode_before' => $before['mode'] ?? null,
            'mismatches_before' => $mismatches,
            'allowed_mismatches' => NEXORA_RC93_REPAIR_ALLOWED_MISMATCHES,
            'installation_lock_sha256_before' => $lockShaBefore,
            'fields_to_update' => array_keys($updates),
            'mutation_performed' => false,
        ]);
    }

    $backupDirectory = nexoraRc93RepairTargetDirectory($target, ['storage', 'app', 'nexora', 'repair-backups'], true);
    if ($backupDirectory === null) {
        nexoraRc93RepairFail('Repair backup directory is redirected or outside the exact target. Installation metadata was not changed.');
    }
    $receiptDirectory = nexoraRc93RepairTargetDirectory($target, ['storage', 'app', 'nexora', 'runtime', 'repair-receipts'], true);
    if ($receiptDirectory === null) {
        nexoraRc93RepairFail('Repair receipt directory is redirected or outside the exact target. Installation metadata was not changed.');
    }

    $stamp = gmdate('Ymd\\THis\\Z');
    $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.'installed-lock-rc93-'.$stamp.'-'.$lockShaBefore.'.json';
    $atomic->write($backupPath, $lockBytes, 0700, 0600);
    if (! is_file($backupPath) || ! hash_equals($lockShaBefore, nexoraRc93RepairHashFile($backupPath))) {
        nexoraRc93RepairFail('Sealed-lock backup verification failed. Installation metadata was not changed.', [
            'backup_path' => $backupPath,
        ]);
    }

    $rollback = static function (string $reason) use ($atomic, $lockPath, $lockBytes, $lockShaBefore, $backupPath): never {
        $rollbackError = null;
        try {
            $atomic->write($lockPath, $lockBytes, 0755, 0600);
            $restoredSha = nexoraRc93RepairHashFile($lockPath);
            if (! hash_equals($lockShaBefore, $restoredSha)) {
                throw new RuntimeException('Restored installation lock SHA-256 does not match the pre-repair lock.');
            }
        } catch (Throwable $exception) {
            $rollbackError = $exception->getMessage();
        }

        nexoraRc93RepairFail('rc.93 identity convergence failed; the bounded installation-lock mutation was rolled back when possible.', [
            'reason' => $reason,
            'backup_path' => $backupPath,
            'rollback_status' => $rollbackError === null ? 'pass' : 'fail',
            'rollback_error' => $rollbackError,
        ]);
    };

    try {
        $installation->updateMetadata($updates);
        nexoraRc93RepairForgetRuntimeMemoization($deployment, $environment, $activation, $services, $processes);
        $after = $guard->assess();
        $afterMismatches = is_array($after) ? nexoraRc93RepairMismatches($after) : ['invalid-assessment'];
        $afterCompatible = is_array($after) && ($after['compatible'] ?? false) === true;
        $afterMode = is_array($after) ? (string) ($after['mode'] ?? '') : '';

        if (! $afterCompatible || $afterMismatches !== [] || $afterMode !== 'installed-data-plane') {
            $rollback('Post-write compatibility did not converge to compatible=true, mismatches=[], mode=installed-data-plane.');
        }
    } catch (Throwable $exception) {
        $rollback($exception->getMessage());
    }

    $lockShaAfter = nexoraRc93RepairHashFile($lockPath);
    if (hash_equals($lockShaBefore, $lockShaAfter)) {
        $rollback('Installation lock did not change after an apply-mode repair.');
    }

    $receipt = [
        'schema' => 1,
        'status' => 'repaired',
        'tool' => 'rc93-post-install-identity-repair',
        'target_version' => NEXORA_RC93_REPAIR_EXPECTED_VERSION,
        'target_path' => $target,
        'repaired_mismatches' => $mismatches,
        'allowed_mismatches' => NEXORA_RC93_REPAIR_ALLOWED_MISMATCHES,
        'installation_lock_sha256_before' => $lockShaBefore,
        'installation_lock_sha256_after' => $lockShaAfter,
        'backup_path' => $backupPath,
        'repaired_at' => gmdate(DATE_ATOM),
        'target_verification_still_required' => [
            'php artisan nexora:runtime:compatibility-status --deep',
            'php artisan nexora:runtime:post-install-status --assert-ready',
            'browser /login',
        ],
    ];
    $receiptForHash = $receipt;
    ksort($receiptForHash, SORT_STRING);
    $receipt['receipt_sha256'] = nexoraRc93RepairHash(json_encode(
        $receiptForHash,
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ));
    $receiptPath = $receiptDirectory.DIRECTORY_SEPARATOR.'rc93-post-install-'.$stamp.'.json';
    $atomic->write(
        $receiptPath,
        json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        0700,
        0600,
    );

    nexoraRc93RepairEmit([
        'status' => 'pass',
        'mode' => 'applied',
        'message' => 'The bounded rc.93 installation identity repair converged. This is repair evidence, not final target certification.',
        'target' => $target,
        'repaired_mismatches' => $mismatches,
        'installation_lock_sha256_before' => $lockShaBefore,
        'installation_lock_sha256_after' => $lockShaAfter,
        'backup_path' => $backupPath,
        'receipt_path' => $receiptPath,
        'mutation_performed' => true,
        'next_commands' => [
            'php artisan nexora:runtime:compatibility-status --deep',
            'php artisan nexora:runtime:post-install-status --assert-ready',
        ],
    ]);
} catch (Throwable $exception) {
    nexoraRc93RepairFail('Repair-pack execution failed before certified convergence.', [
        'exception' => get_class($exception),
        'message_detail' => $exception->getMessage(),
        'mutation_state' => 'unknown; if apply mode had begun, inspect the protected repair backup before retrying',
    ]);
} finally {
    if (is_string($previousCwd) && $previousCwd !== '') {
        @chdir($previousCwd);
    }
}