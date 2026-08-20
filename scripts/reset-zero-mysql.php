<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'nexora';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'root';
if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) { fwrite(STDERR, "Unsafe database name.\n"); exit(1); }
try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    fwrite(STDOUT, "[Nexora] MySQL database [{$database}] removed for zero-install test.\n");
} catch (Throwable $e) { fwrite(STDERR, "[Nexora] Database reset failed: {$e->getMessage()}\n"); exit(1); }
