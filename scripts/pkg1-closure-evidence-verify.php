<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/pkg1-closure.php';
require_once $root.'/scripts/lib/pkg1-build-identity.php';

$fail = static function (string $message): never {
    fwrite(STDERR, "[PKG-1 Evidence] FAIL — {$message}\n");
    exit(1);
};
$read = static function (string $path) use ($fail): array {
    if (! is_file($path)) {
        $fail('Required closure artifact missing: '.$path);
    }
    try {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        $fail('Invalid JSON ['.$path.']: '.$exception->getMessage());
    }
    if (! is_array($data)) {
        $fail('Closure artifact root must be an object: '.$path);
    }
    return $data;
};
$hash = static fn (string $path): ?string => is_file($path)
    ? (hash_file('sha256', $path) ?: null)
    : null;
$canonicalize = static function (mixed $value) use (&$canonicalize): mixed {
    if (! is_array($value)) {
        return $value;
    }
    if (array_is_list($value)) {
        return array_map($canonicalize, $value);
    }
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) {
        $value[$key] = $canonicalize($item);
    }
    return $value;
};

$path = $root.'/storage/app/nexora/pkg1/closure.json';
$closure = $read($path);
$storedSeal = strtolower(trim((string) ($closure['receipt_sha256'] ?? '')));
if (preg_match('/^[a-f0-9]{64}$/', $storedSeal) !== 1) {
    $fail('Closure receipt SHA-256 is missing or invalid.');
}
$unsigned = $closure;
unset($unsigned['receipt_sha256']);
$actualSeal = hash(
    'sha256',
    json_encode($canonicalize($unsigned), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
);
if (! hash_equals($storedSeal, $actualSeal)) {
    $fail('Closure receipt integrity seal does not match.');
}

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$source = nexoraComputeSourceAttestation($root);
if (($closure['status'] ?? null) !== 'pass'
    || ($closure['package'] ?? null) !== 'PKG-1'
    || ($closure['platform_version'] ?? null) !== $version
    || ($closure['source_tree_sha256'] ?? null) !== $source['tree_sha256']) {
    $fail('Closure identity does not match the current exact source.');
}

$expected = [
    'c1_evidence_sha256' => $root.'/storage/app/nexora/n1-c1/latest.json',
    'composer_lock_sha256' => $root.'/composer.lock',
    'package_lock_sha256' => $root.'/package-lock.json',
    'reviewed_locks_sha256' => $root.'/storage/app/nexora/dependency-intake/reviewed-locks.json',
    'build_assets_sha256' => $root.'/storage/app/nexora/certification/build-assets.json',
    'pkg1_build_input_sha256' => $root.'/storage/app/nexora/certification/pkg1-build-input.json',
    'installation_lock_sha256' => $root.'/storage/app/nexora/installed.lock',
    'post_install_handoff_sha256' => $root.'/storage/app/nexora/runtime/post-install-handoff.json',
    'usable_smoke_sha256' => $root.'/storage/app/nexora/pkg1/usable-smoke.json',
];
foreach ($expected as $key => $artifactPath) {
    $actual = $hash($artifactPath);
    if ($actual === null || ($closure['artifacts'][$key] ?? null) !== $actual) {
        $fail("Closure artifact binding mismatch [{$key}].");
    }
}

$buildInput = $read($root.'/storage/app/nexora/certification/pkg1-build-input.json');
$currentBuildIdentity = nexoraPkg1BuildIdentity($root);
if (($buildInput['status'] ?? null) !== 'pass'
    || ($buildInput['identity_stable'] ?? false) !== true
    || ($buildInput['identity_sha256'] ?? null) !== $currentBuildIdentity['identity_sha256']
    || ($buildInput['post_build_identity_sha256'] ?? null) !== $currentBuildIdentity['identity_sha256']) {
    $fail('PKG-1 build provenance is stale for the current source/lock/config identity.');
}

$smoke = $read($root.'/storage/app/nexora/pkg1/usable-smoke.json');
if (($smoke['status'] ?? null) !== 'pass'
    || ($smoke['live_auth_smoke']['status'] ?? null) !== 'pass') {
    $fail('Usable smoke/live login evidence is not PASS.');
}

foreach ([
    [PHP_BINARY, 'scripts/n1-c1-evidence-verify.php'],
    [PHP_BINARY, 'artisan', 'nexora:install:lock-status', '--assert-valid'],
    [PHP_BINARY, 'artisan', 'nexora:runtime:post-install-status', '--assert-ready'],
] as $command) {
    $result = nexoraPkg1Run($command, $root);
    if ($result['exit_code'] !== 0) {
        $fail('Current target no longer satisfies closure verifier command: '.implode(' ', $command));
    }
}

fwrite(
    STDOUT,
    "[PKG-1 Evidence] PASS — exact source, C1 14/14 evidence, reviewed locks, build assets, installed lock, runtime handoff and live login/admin smoke remain bound to the sealed PKG-1 closure receipt.\n",
);
