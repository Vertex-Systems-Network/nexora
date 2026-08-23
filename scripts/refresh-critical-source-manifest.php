<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifestPath = $root.'/bootstrap/nexora-source-manifest.json';
$configPath = $root.'/config/installer.php';
$write = in_array('--write', $argv, true);
$check = in_array('--check', $argv, true) || ! $write;

$fail = static function (string $message): never {
    fwrite(STDERR, '[Nexora Critical Source Manifest] FAILED — '.$message.PHP_EOL);
    exit(1);
};

if ($write && in_array('--check', $argv, true)) {
    $fail('Choose either --write or --check, not both.');
}

$manifestRaw = @file_get_contents($manifestPath);
if (! is_string($manifestRaw) || $manifestRaw === '') {
    $fail('Critical source manifest is missing or unreadable.');
}

try {
    $manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    $fail('Critical source manifest JSON is invalid: '.$exception->getMessage());
}

if (! is_array($manifest) || ! isset($manifest['files']) || ! is_array($manifest['files']) || $manifest['files'] === []) {
    $fail('Critical source manifest must contain a non-empty files map.');
}

$files = $manifest['files'];
foreach ($files as $relative => $expectedHash) {
    if (! is_string($relative)
        || $relative === ''
        || str_contains($relative, "\0")
        || str_contains(str_replace('\\', '/', $relative), '../')
        || str_starts_with(str_replace('\\', '/', $relative), '/')
        || preg_match('/^[A-Za-z]:[\\\\\/]/', $relative) === 1) {
        $fail('Unsafe critical source path in manifest.');
    }

    $path = $root.'/'.str_replace('\\', '/', $relative);
    if (! is_file($path)) {
        $fail("Critical source file is missing: {$relative}");
    }

    $hash = hash_file('sha256', $path);
    if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
        $fail("Unable to hash critical source file: {$relative}");
    }

    $files[$relative] = $hash;
}

$manifest['files'] = $files;
try {
    $nextManifest = json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ).PHP_EOL;
} catch (Throwable $exception) {
    $fail('Unable to encode critical source manifest: '.$exception->getMessage());
}

$manifestSha = hash('sha256', $nextManifest);
$configRaw = @file_get_contents($configPath);
if (! is_string($configRaw) || $configRaw === '') {
    $fail('Installer configuration is missing or unreadable.');
}

$count = 0;
$nextConfig = preg_replace(
    "/('manifest_sha256'\\s*=>\\s*')[a-f0-9]{64}(')/i",
    '${1}'.$manifestSha.'${2}',
    $configRaw,
    1,
    $count,
);
if (! is_string($nextConfig) || $count !== 1) {
    $fail('Installer source manifest SHA-256 setting could not be updated unambiguously.');
}

$manifestChanged = ! hash_equals(hash('sha256', $manifestRaw), hash('sha256', $nextManifest));
$configChanged = ! hash_equals(hash('sha256', $configRaw), hash('sha256', $nextConfig));

if ($write) {
    if (file_put_contents($manifestPath, $nextManifest, LOCK_EX) !== strlen($nextManifest)) {
        $fail('Unable to write the critical source manifest.');
    }
    if (file_put_contents($configPath, $nextConfig, LOCK_EX) !== strlen($nextConfig)) {
        $fail('Unable to write the installer manifest binding.');
    }

    $writtenManifestSha = hash_file('sha256', $manifestPath);
    if (! is_string($writtenManifestSha) || ! hash_equals($manifestSha, $writtenManifestSha)) {
        $fail('Written critical source manifest does not match the computed SHA-256.');
    }

    fwrite(
        STDOUT,
        '[Nexora Critical Source Manifest] RESEALED — '.count($files).' files; manifest sha256='.$manifestSha.PHP_EOL,
    );
    exit(0);
}

if ($check && ($manifestChanged || $configChanged)) {
    fwrite(STDERR, '[Nexora Critical Source Manifest] STALE'.PHP_EOL);
    if ($manifestChanged) {
        fwrite(STDERR, ' - critical source file hashes differ from the manifest'.PHP_EOL);
    }
    if ($configChanged) {
        fwrite(STDERR, ' - config/installer.php manifest_sha256 does not bind the canonical manifest bytes'.PHP_EOL);
    }
    fwrite(STDERR, 'Run `php scripts/refresh-critical-source-manifest.php --write` on a trusted development checkout, review the diff, then rerun --check.'.PHP_EOL);
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Critical Source Manifest] PASS — '.count($files).' files; manifest sha256='.$manifestSha.PHP_EOL,
);
