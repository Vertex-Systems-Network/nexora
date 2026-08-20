<?php

declare(strict_types=1);

/**
 * Safe pre-Laravel installation environment bootstrap.
 *
 * Nexora must be able to render its deployment/installation UI before a root
 * .env exists. Runtime values are loaded from the root .env when available,
 * otherwise from the protected storage fallback. Before installation, a
 * persistent bootstrap key under storage keeps APP_KEY stable without making
 * the project root writable.
 */
require_once __DIR__.'/nexora-runtime-bootstrap.php';

$root = dirname(__DIR__);
$installedLock = $root.'/storage/app/nexora/installed.lock';
$rootEnv = $root.'/.env';
$fallbackDir = $root.'/storage/app/nexora/environment';
$fallbackEnv = $fallbackDir.'/.env';
$exampleEnv = $root.'/.env.example';
$bootstrapKey = $fallbackDir.'/bootstrap.key';
$activeMarker = $fallbackDir.'/active';

if (! is_dir($fallbackDir) && ! @mkdir($fallbackDir, 0775, true) && ! is_dir($fallbackDir)) {
    define('NEXORA_INSTALL_BOOTSTRAP_ERROR', 'Nexora cannot prepare its protected installation environment directory under storage/app/nexora.');
    return;
}

/** @return array<string,string> */
$parseEnvFile = static function (string $path): array {
    if (! is_file($path) || ! is_readable($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (preg_match('/^[A-Z0-9_]+$/', $key) !== 1) {
            continue;
        }
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $values[$key] = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $value);
    }

    return $values;
};

$exportEnv = static function (string $key, string $value, bool $overwrite = false): void {
    $existing = getenv($key);
    if (! $overwrite && $existing !== false && $existing !== '') {
        return;
    }
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$activeEnvironment = is_file($activeMarker) && is_readable($activeMarker)
    ? strtolower(trim((string) @file_get_contents($activeMarker)))
    : '';

// An installed deployment must never silently fall through to a different
// environment file when an explicit active marker exists. Doing so can boot
// with stale credentials/config after a file move or failed deployment.
if (is_file($installedLock) && $activeEnvironment !== '') {
    if (! in_array($activeEnvironment, ['root', 'fallback'], true)) {
        define('NEXORA_INSTALL_BOOTSTRAP_ERROR', 'Installed Nexora has an invalid environment active marker. Expected root or fallback.');
        return;
    }
    $markedSource = $activeEnvironment === 'root' ? $rootEnv : $fallbackEnv;
    if (! is_readable($markedSource)) {
        define('NEXORA_INSTALL_BOOTSTRAP_ERROR', 'Installed Nexora active environment source is missing or unreadable. Refusing to fall back to a different environment file.');
        return;
    }
}

if ($activeEnvironment === 'fallback' && is_readable($fallbackEnv)) {
    $source = $fallbackEnv;
} elseif ($activeEnvironment === 'root' && is_readable($rootEnv)) {
    $source = $rootEnv;
} else {
    $source = is_readable($rootEnv)
        ? $rootEnv
        : (is_readable($fallbackEnv) ? $fallbackEnv : (is_readable($exampleEnv) ? $exampleEnv : null));
}
if ($source !== null) {
    foreach ($parseEnvFile($source) as $key => $value) {
        $exportEnv($key, $value);
    }
}

$key = (string) (getenv('APP_KEY') ?: '');
if ($key === '') {
    if (is_file($bootstrapKey) && is_readable($bootstrapKey)) {
        $key = trim((string) @file_get_contents($bootstrapKey));
    }
    if ($key === '') {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $temporaryKey = $fallbackDir.'/.nexora-bootstrap-key-'.bin2hex(random_bytes(6)).'.tmp';
        $handle = @fopen($temporaryKey, 'xb');
        $persisted = false;
        if (is_resource($handle)) {
            try {
                $persisted = @flock($handle, LOCK_EX) && @fwrite($handle, $key) === strlen($key) && @fflush($handle);
                if ($persisted && function_exists('fsync')) $persisted = (bool) @fsync($handle);
            } finally {
                @flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
        if (! $persisted || ! @rename($temporaryKey, $bootstrapKey)) {
            @unlink($temporaryKey);
            define('NEXORA_INSTALL_BOOTSTRAP_ERROR', 'Nexora could not persist the protected bootstrap application key atomically.');
            return;
        }
        @chmod($bootstrapKey, 0600);
    }
    $exportEnv('APP_KEY', $key, true);
}

// Installer-safe defaults. A completed install writes production values.
$exportEnv('APP_NAME', 'Nexora');
$exportEnv('APP_ENV', is_file($installedLock) ? 'production' : 'local');
$exportEnv('APP_DEBUG', is_file($installedLock) ? 'false' : 'true');
$exportEnv('APP_URL', 'http://localhost');
$exportEnv('APP_LOCALE', 'en');

if (! is_file($installedLock)) {
    $exportEnv('SESSION_DRIVER', 'file', true);
    $exportEnv('CACHE_STORE', 'file', true);
    $exportEnv('QUEUE_CONNECTION', 'sync', true);
}

// Diagnostics flags only; these are not fatal installation blockers.
define('NEXORA_ENV_ROOT_PATH', $rootEnv);
define('NEXORA_ENV_FALLBACK_PATH', $fallbackEnv);
define('NEXORA_ENV_SOURCE', $source ?? 'runtime-bootstrap');
define('NEXORA_ENV_ACTIVE_MODE', $activeEnvironment !== '' ? $activeEnvironment : 'auto');
define('NEXORA_ENV_ROOT_WRITABLE', (is_file($rootEnv) && is_writable($rootEnv)) || (! is_file($rootEnv) && is_writable($root)));
