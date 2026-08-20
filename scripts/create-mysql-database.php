<?php

declare(strict_types=1);

/**
 * Creates the configured MySQL database without booting Laravel.
 * Environment variables win; .env values are used next; Nexora local defaults are last.
 */
function nexoraEnv(string $key, string $default): string
{
    $environment = getenv($key);
    if ($environment !== false && $environment !== '') {
        return $environment;
    }

    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $path = dirname(__DIR__).'/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                $fileValues[$name] = trim($value, "\"'");
            }
        }
    }

    return (string) ($fileValues[$key] ?? $default);
}

$host = nexoraEnv('DB_HOST', '127.0.0.1');
$port = nexoraEnv('DB_PORT', '3306');
$database = nexoraEnv('DB_DATABASE', 'nexora');
$username = nexoraEnv('DB_USERNAME', 'root');
$password = nexoraEnv('DB_PASSWORD', 'root');

if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    fwrite(STDERR, "[Nexora] Unsafe MySQL database name [{$database}]. Use letters, numbers and underscores only.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    fwrite(STDOUT, "[Nexora] MySQL database [{$database}] is ready.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora] Unable to prepare MySQL database [{$database}]: {$exception->getMessage()}\n");
    exit(1);
}
