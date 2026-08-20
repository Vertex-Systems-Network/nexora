<?php

declare(strict_types=1);

require_once __DIR__.'/target-composer.php';

/** @return array<string,mixed> */
function nexoraCollectDependencyToolchain(string $root): array
{
    $policy = require $root.'/config/nexora-dependencies.php';
    $composer = nexoraLocateTargetComposer($root);
    $node = nexoraDependencyExecutableIdentity($root, 'node', ['node', '--version']);
    $npm = nexoraDependencyExecutableIdentity($root, 'npm', ['npm', '--version']);
    $phpSha = is_file(PHP_BINARY) ? (hash_file('sha256', PHP_BINARY) ?: null) : null;
    $composerSha = nexoraComposerBinarySha($root, $composer);

    $data = [
        'schema' => 1,
        'php' => [
            'version' => PHP_VERSION,
            'binary' => basename(PHP_BINARY),
            'binary_sha256' => $phpSha,
        ],
        'composer' => [
            'available' => (bool) ($composer['available'] ?? false),
            'version' => $composer['version'] ?? null,
            'source' => $composer['source'] ?? null,
            'binary_sha256' => $composerSha,
        ],
        'node' => $node,
        'npm' => $npm,
        'policy' => [
            'php' => $policy['php'] ?? null,
            'composer' => $policy['composer'] ?? null,
            'node' => $policy['node'] ?? null,
            'npm' => $policy['npm'] ?? null,
        ],
    ];
    $data['errors'] = nexoraValidateDependencyToolchain($data, $policy);
    $data['status'] = $data['errors'] === [] ? 'pass' : 'fail';
    $data['fingerprint_sha256'] = nexoraDependencyToolchainFingerprint($data);

    return $data;
}

/** @return array{available:bool,version:?string,binary:?string,binary_sha256:?string,execution_mode:string,launcher:?string} */
function nexoraDependencyExecutableIdentity(string $root, string $name, array $versionCommand): array
{
    $environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $normalized = nexoraNormalizeTargetCommand($versionCommand, $root, $environment);
    $probe = nexoraRunTargetCommand($versionCommand, $root, $environment);
    $version = $probe['exit_code'] === 0
        ? nexoraParseToolVersion($probe['stdout'] !== '' ? $probe['stdout'] : $probe['stderr'])
        : null;
    $locator = PHP_OS_FAMILY === 'Windows' ? ['where.exe', $name] : ['which', $name];
    $where = nexoraRunTargetCommand($locator, $root, $environment);
    $launcherPath = null;
    if ($where['exit_code'] === 0) {
        foreach (preg_split('/\R+/', trim($where['stdout'])) ?: [] as $candidate) {
            $candidate = trim($candidate, " \t\r\n\"'");
            if ($candidate !== '' && is_file($candidate)) {
                $launcherPath = $candidate;
                break;
            }
        }
    }

    $fingerprintPath = null;
    $executionMode = 'direct';
    if (PHP_OS_FAMILY === 'Windows'
        && count($normalized) >= 2
        && strtolower(basename(str_replace('\\', '/', $normalized[0]))) === 'node.exe'
        && in_array(strtolower(basename(str_replace('\\', '/', $normalized[1]))), ['npm-cli.js', 'npx-cli.js'], true)) {
        $fingerprintPath = is_file($normalized[1]) ? $normalized[1] : null;
        $executionMode = 'node-cli';
    } elseif (isset($normalized[0]) && is_file($normalized[0])) {
        $fingerprintPath = $normalized[0];
    } else {
        $fingerprintPath = $launcherPath;
    }

    return [
        'available' => $probe['exit_code'] === 0,
        'version' => $version,
        'binary' => $fingerprintPath !== null ? basename($fingerprintPath) : null,
        'binary_sha256' => $fingerprintPath !== null ? (hash_file('sha256', $fingerprintPath) ?: null) : null,
        'execution_mode' => $executionMode,
        'launcher' => $launcherPath !== null ? basename($launcherPath) : null,
    ];
}

/** @param array<string,mixed> $composer */
function nexoraComposerBinarySha(string $root, array $composer): ?string
{
    $path = (string) ($composer['path'] ?? '');
    if ($path !== '' && $path !== 'composer' && is_file($path)) {
        return hash_file('sha256', $path) ?: null;
    }
    $identity = nexoraDependencyExecutableIdentity($root, 'composer', ['composer', '--version', '--no-ansi']);
    return $identity['binary_sha256'];
}

/** @param array<string,mixed> $toolchain @param array<string,mixed> $policy @return list<string> */
function nexoraValidateDependencyToolchain(array $toolchain, array $policy): array
{
    $errors = [];
    $php = (string) ($toolchain['php']['version'] ?? '0.0.0');
    if (version_compare($php, (string) $policy['php']['minimum'], '<')
        || version_compare($php, (string) $policy['php']['maximum_exclusive'], '>=')) {
        $errors[] = 'PHP version is outside the certified dependency-toolchain range.';
    }

    $composer = (string) ($toolchain['composer']['version'] ?? '0.0.0');
    if (($toolchain['composer']['available'] ?? false) !== true
        || version_compare($composer, (string) $policy['composer']['minimum'], '<')
        || version_compare($composer, (string) $policy['composer']['maximum_exclusive'], '>=')) {
        $errors[] = 'Composer version is unavailable or outside the certified range.';
    }

    foreach (['node', 'npm'] as $name) {
        $version = (string) ($toolchain[$name]['version'] ?? '0.0.0');
        $major = (int) explode('.', ltrim($version, 'v'))[0];
        $minimum = (int) $policy[$name]['minimum_major'];
        $maximum = (int) $policy[$name]['maximum_major_exclusive'];
        if (($toolchain[$name]['available'] ?? false) !== true || $major < $minimum || $major >= $maximum) {
            $errors[] = ucfirst($name).' version is unavailable or outside the certified major range.';
        }
    }

    foreach (['php', 'composer', 'node', 'npm'] as $name) {
        if (! is_string($toolchain[$name]['binary_sha256'] ?? null)) {
            $errors[] = "Unable to fingerprint {$name} executable.";
        }
    }

    return array_values(array_unique($errors));
}

/** @param array<string,mixed> $toolchain */
function nexoraDependencyToolchainFingerprint(array $toolchain): string
{
    $copy = $toolchain;
    unset($copy['fingerprint_sha256'], $copy['errors'], $copy['status']);
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

    return hash('sha256', json_encode(
        $canonicalize($copy),
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    ));
}
