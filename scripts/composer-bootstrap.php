<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/target-composer.php';

$json = in_array('--json', $argv, true);
$force = in_array('--force', $argv, true);
$policy = require $root.'/config/nexora-dependencies.php';
$installDirectory = $root.'/storage/app/nexora/tools/composer';
$pharPath = $installDirectory.'/composer.phar';
$attestationPath = $installDirectory.'/bootstrap-attestation.json';
$installerPath = $installDirectory.'/composer-setup.php';
$signatureUrl = 'https://composer.github.io/installer.sig';
$installerUrl = 'https://getcomposer.org/installer';
$offlinePhar = trim((string) (getenv('NEXORA_COMPOSER_PHAR') ?: ''));
$offlineSha256 = strtolower(trim((string) (getenv('NEXORA_COMPOSER_PHAR_SHA256') ?: '')));

/** @return array{ok:bool,body:string,error:?string,transport:?string} */
$download = static function (string $url): array {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'follow_location' => 1,
            'max_redirects' => 5,
            'user_agent' => 'Nexora-PKG1-Composer-Bootstrap/1',
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    if (is_string($body) && $body !== '') {
        return ['ok' => true, 'body' => $body, 'error' => null, 'transport' => 'php-stream'];
    }

    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        if ($handle !== false) {
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Nexora-PKG1-Composer-Bootstrap/1',
            ]);
            $response = curl_exec($handle);
            $error = curl_error($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);
            if (is_string($response) && $response !== '' && $status >= 200 && $status < 300) {
                return ['ok' => true, 'body' => $response, 'error' => null, 'transport' => 'php-curl'];
            }
            return [
                'ok' => false,
                'body' => '',
                'error' => $error !== '' ? $error : "HTTP {$status}",
                'transport' => 'php-curl',
            ];
        }
    }

    $last = error_get_last();
    return [
        'ok' => false,
        'body' => '',
        'error' => is_array($last) ? (string) ($last['message'] ?? 'download failed') : 'download failed',
        'transport' => 'php-stream',
    ];
};

/** @return never */
$fail = static function (string $message, array $details = []) use ($json): never {
    $payload = [
        'schema' => 1,
        'status' => 'blocked',
        'message' => $message,
        'details' => $details,
    ];
    if ($json) {
        fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    } else {
        fwrite(STDERR, "[Nexora Composer Bootstrap] BLOCKED — {$message}\n");
        foreach ($details as $key => $value) {
            if (is_scalar($value) || $value === null) {
                fwrite(STDERR, " - {$key}: ".(string) $value."\n");
            }
        }
    }
    exit(2);
};

$existing = nexoraLocateTargetComposer($root);
if (($existing['available'] ?? false) === true && ! $force) {
    $payload = [
        'schema' => 1,
        'status' => 'pass',
        'action' => 'existing-composer',
        'version' => $existing['version'] ?? null,
        'source' => $existing['source'] ?? null,
        'path' => $existing['path'] ?? null,
    ];
    fwrite(STDOUT, $json
        ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        : '[Nexora Composer Bootstrap] PASS — existing Composer '.($payload['version'] ?? 'unknown').' via '.($payload['source'] ?? 'unknown').".\n");
    exit(0);
}

if (! is_dir($installDirectory)
    && ! mkdir($installDirectory, 0700, true)
    && ! is_dir($installDirectory)) {
    $fail('Unable to create the local Composer tool directory.', ['path' => $installDirectory]);
}

// Offline/enterprise handoff: accept only an explicitly supplied PHAR with an exact SHA-256.
// The external file is never executed directly; a verified copy is staged into Nexora storage.
if ($offlinePhar !== '') {
    if (! is_file($offlinePhar)) {
        $fail('NEXORA_COMPOSER_PHAR does not point to a readable file.', ['path' => $offlinePhar]);
    }
    if (preg_match('/^[a-f0-9]{64}$/', $offlineSha256) !== 1) {
        $fail('NEXORA_COMPOSER_PHAR_SHA256 must be the exact 64-character SHA-256 for the offline Composer PHAR.');
    }
    $actualOfflineSha256 = strtolower((string) (hash_file('sha256', $offlinePhar) ?: ''));
    if (! hash_equals($offlineSha256, $actualOfflineSha256)) {
        $fail('Offline Composer PHAR SHA-256 verification failed.', [
            'expected_sha256' => $offlineSha256,
            'actual_sha256' => $actualOfflineSha256,
        ]);
    }
    $bytes = @file_get_contents($offlinePhar);
    if (! is_string($bytes) || $bytes === '') {
        $fail('Unable to read the verified offline Composer PHAR.', ['path' => $offlinePhar]);
    }
    if (@file_put_contents($pharPath, $bytes, LOCK_EX) !== strlen($bytes)) {
        $fail('Unable to stage the verified offline Composer PHAR.', ['path' => $pharPath]);
    }
    @chmod($pharPath, 0600);

    $environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
    $probe = nexoraRunTargetCommand([PHP_BINARY, $pharPath, '--version', '--no-ansi'], $root, $environment);
    if ($probe['exit_code'] !== 0) {
        @unlink($pharPath);
        $fail('Verified offline Composer PHAR cannot execute with the current PHP binary.', [
            'stderr' => substr((string) $probe['stderr'], 0, 800),
        ]);
    }
    $raw = trim($probe['stdout'] !== '' ? $probe['stdout'] : $probe['stderr']);
    $version = nexoraParseToolVersion($raw);
    $minimum = (string) ($policy['composer']['minimum'] ?? '2.7.0');
    $maximum = (string) ($policy['composer']['maximum_exclusive'] ?? '3.0.0');
    if (! is_string($version) || version_compare($version, $minimum, '<') || version_compare($version, $maximum, '>=')) {
        @unlink($pharPath);
        $fail('Offline Composer version is outside the certified range.', [
            'version' => $version,
            'minimum' => $minimum,
            'maximum_exclusive' => $maximum,
        ]);
    }

    $attestation = [
        'schema' => 1,
        'status' => 'pass',
        'source' => 'offline-explicit-sha256-handoff',
        'composer_version' => $version,
        'composer_phar_sha256' => $actualOfflineSha256,
        'php_version' => PHP_VERSION,
        'php_binary_sha256' => is_file(PHP_BINARY) ? (hash_file('sha256', PHP_BINARY) ?: null) : null,
        'created_at_utc' => gmdate('c'),
    ];
    $attestation['attestation_sha256'] = hash('sha256', json_encode($attestation, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    file_put_contents($attestationPath, json_encode($attestation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX);
    @chmod($attestationPath, 0600);

    $payload = [
        'schema' => 1,
        'status' => 'pass',
        'action' => 'offline-verified-composer',
        'version' => $version,
        'path' => str_replace('\\', '/', $pharPath),
        'composer_phar_sha256' => $actualOfflineSha256,
    ];
    fwrite(STDOUT, $json
        ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        : "[Nexora Composer Bootstrap] PASS — offline Composer {$version} SHA-256 verified and ready.\n");
    exit(0);
}

$signature = $download($signatureUrl);
if (! $signature['ok']) {
    $fail(
        'Unable to download the official Composer installer signature. Check DNS/TLS/internet access.',
        ['url' => $signatureUrl, 'transport' => $signature['transport'], 'error' => $signature['error']],
    );
}
$expectedSha384 = strtolower(trim($signature['body']));
if (preg_match('/^[a-f0-9]{96}$/', $expectedSha384) !== 1) {
    $fail('Official Composer installer signature response is invalid.', ['url' => $signatureUrl]);
}

$installer = $download($installerUrl);
if (! $installer['ok']) {
    $fail(
        'Unable to download the official Composer installer. Check DNS/TLS/internet access.',
        ['url' => $installerUrl, 'transport' => $installer['transport'], 'error' => $installer['error']],
    );
}
$actualSha384 = hash('sha384', $installer['body']);
if (! hash_equals($expectedSha384, strtolower($actualSha384))) {
    $fail('Composer installer SHA-384 verification failed.', [
        'expected_sha384' => $expectedSha384,
        'actual_sha384' => $actualSha384,
    ]);
}
if (file_put_contents($installerPath, $installer['body'], LOCK_EX) !== strlen($installer['body'])) {
    $fail('Unable to stage the verified Composer installer.', ['path' => $installerPath]);
}
@chmod($installerPath, 0600);

$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$install = nexoraRunTargetCommand([
    PHP_BINARY,
    $installerPath,
    '--2',
    '--quiet',
    '--install-dir='.$installDirectory,
    '--filename=composer.phar',
], $root, $environment);
@unlink($installerPath);
if ($install['exit_code'] !== 0 || ! is_file($pharPath)) {
    $fail('Verified Composer installer did not produce composer.phar.', [
        'exit_code' => $install['exit_code'],
        'stderr' => substr((string) $install['stderr'], 0, 800),
    ]);
}
@chmod($pharPath, 0600);

$probe = nexoraRunTargetCommand([PHP_BINARY, $pharPath, '--version', '--no-ansi'], $root, $environment);
if ($probe['exit_code'] !== 0) {
    @unlink($pharPath);
    $fail('Bootstrapped composer.phar cannot execute with the current PHP binary.', [
        'stderr' => substr((string) $probe['stderr'], 0, 800),
    ]);
}
$raw = trim($probe['stdout'] !== '' ? $probe['stdout'] : $probe['stderr']);
$version = nexoraParseToolVersion($raw);
$minimum = (string) ($policy['composer']['minimum'] ?? '2.7.0');
$maximum = (string) ($policy['composer']['maximum_exclusive'] ?? '3.0.0');
if (! is_string($version)
    || version_compare($version, $minimum, '<')
    || version_compare($version, $maximum, '>=')) {
    @unlink($pharPath);
    $fail('Bootstrapped Composer version is outside the certified range.', [
        'version' => $version,
        'minimum' => $minimum,
        'maximum_exclusive' => $maximum,
    ]);
}

$attestation = [
    'schema' => 1,
    'status' => 'pass',
    'source' => 'official-programmatic-installer',
    'signature_url' => $signatureUrl,
    'installer_url' => $installerUrl,
    'installer_sha384' => $actualSha384,
    'composer_version' => $version,
    'composer_phar_sha256' => hash_file('sha256', $pharPath) ?: null,
    'php_version' => PHP_VERSION,
    'php_binary_sha256' => is_file(PHP_BINARY) ? (hash_file('sha256', PHP_BINARY) ?: null) : null,
    'signature_transport' => $signature['transport'],
    'installer_transport' => $installer['transport'],
    'created_at_utc' => gmdate('c'),
];
$attestation['attestation_sha256'] = hash('sha256', json_encode(
    $attestation,
    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
));
file_put_contents(
    $attestationPath,
    json_encode($attestation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    LOCK_EX,
);
@chmod($attestationPath, 0600);

$payload = [
    'schema' => 1,
    'status' => 'pass',
    'action' => 'bootstrapped-local-composer',
    'version' => $version,
    'path' => str_replace('\\', '/', $pharPath),
    'composer_phar_sha256' => $attestation['composer_phar_sha256'],
    'installer_sha384' => $actualSha384,
];
if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
} else {
    fwrite(STDOUT, "[Nexora Composer Bootstrap] PASS — local Composer {$version} verified and ready.\n");
}
