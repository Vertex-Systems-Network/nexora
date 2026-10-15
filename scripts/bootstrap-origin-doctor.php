<?php

declare(strict_types=1);

/**
 * Run before dependency installation. Does not echo credentials or change .env.
 */
require_once __DIR__.'/lib/bootstrap-origin-contracts.php';

$environmentPath = dirname(__DIR__).'/.env';
if (! is_file($environmentPath) || ! is_readable($environmentPath)) {
    fwrite(STDERR, "[Nexora Bootstrap] ERROR: Expected a readable .env file.\n");
    exit(1);
}
$errors = nexoraBootstrapOriginErrors((string) file_get_contents($environmentPath));
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[Nexora Bootstrap] ERROR: {$error}\n");
    }
    fwrite(STDERR, "[Nexora Bootstrap] Set APP_URL and SESSION_SECURE_COOKIE in .env. For local loopback HTTP use http://127.0.0.1:<port> with SESSION_SECURE_COOKIE=false. For real hosts configure HTTPS and SESSION_SECURE_COOKIE=true. No keys were modified.\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Bootstrap] PASS: configured origin and session cookie policy.\n");
