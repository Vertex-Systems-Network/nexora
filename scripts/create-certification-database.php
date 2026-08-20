<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$driver = strtolower((string) (getenv('DB_CONNECTION') ?: 'mysql'));
$database = (string) (getenv('DB_DATABASE') ?: 'nexora_certification');
$host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
$port = (string) (getenv('DB_PORT') ?: match ($driver) { 'pgsql' => '5432', 'sqlsrv' => '1433', default => '3306' });
$username = (string) (getenv('DB_USERNAME') ?: match ($driver) { 'pgsql' => 'postgres', 'sqlsrv' => 'sa', default => 'root' });
$passwordEnv = getenv('DB_PASSWORD');
$password = $passwordEnv === false ? match ($driver) { default => 'root' } : (string) $passwordEnv;

$fail = static function (string $message): never {
    fwrite(STDERR, "[Nexora Certification DB] {$message}\n");
    exit(1);
};

if ($driver === 'sqlite') {
    $path = $database;
    if ($path === '' || $path === ':memory:') {
        $path = $root.'/storage/app/nexora/certification/sqlite.sqlite';
    }
    $realRoot = realpath($root) ?: $root;
    $normalized = str_replace('\\', '/', $path);
    if (! str_starts_with($normalized, str_replace('\\', '/', $realRoot).'/storage/app/nexora/certification/')) {
        $fail('SQLite certification database must live under storage/app/nexora/certification/.');
    }
    $dir = dirname($path);
    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) $fail('Unable to create SQLite certification directory.');
    if (is_file($path) && ! @unlink($path)) $fail('Unable to reset SQLite certification database.');
    if (@touch($path) === false) $fail('Unable to create SQLite certification database.');
    fwrite(STDOUT, "[Nexora Certification DB] SQLite database ready: {$path}\n");
    exit(0);
}

if (preg_match('/^nexora[_-](?:test|testing|cert|certification)[A-Za-z0-9_-]*$/i', $database) !== 1) {
    $fail("Unsafe certification database name [{$database}]. Use a dedicated nexora_testing* or nexora_certification* database.");
}

try {
    if ($driver === 'mysql' || $driver === 'mariadb') {
        if (! extension_loaded('pdo_mysql')) $fail('pdo_mysql extension is required.');
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } elseif ($driver === 'pgsql') {
        if (! extension_loaded('pdo_pgsql')) $fail('pdo_pgsql extension is required.');
        $maintenance = (string) (getenv('NEXORA_CERT_PG_MAINTENANCE_DB') ?: 'postgres');
        $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$maintenance}", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $exists = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = :name');
        $exists->execute(['name'=>$database]);
        if (! $exists->fetchColumn()) $pdo->exec('CREATE DATABASE "'.str_replace('"','""',$database).'"');
    } elseif ($driver === 'sqlsrv') {
        if (! extension_loaded('pdo_sqlsrv')) $fail('pdo_sqlsrv extension is required.');
        $pdo = new PDO("sqlsrv:Server={$host},{$port};Database=master;TrustServerCertificate=1", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $quoted = str_replace(']', ']]', $database);
        $stmt = $pdo->prepare('SELECT DB_ID(?)');
        $stmt->execute([$database]);
        if (! $stmt->fetchColumn()) $pdo->exec("CREATE DATABASE [{$quoted}]");
    } else {
        $fail("Unsupported certification database driver [{$driver}].");
    }
    fwrite(STDOUT, "[Nexora Certification DB] {$driver} database [{$database}] is ready.\n");
} catch (Throwable $exception) {
    $fail("Unable to prepare {$driver} database [{$database}]: {$exception->getMessage()}");
}
