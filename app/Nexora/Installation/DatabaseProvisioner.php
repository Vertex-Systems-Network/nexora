<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Installation\Database\DatabaseDriverRegistry;
use App\Nexora\Installation\Database\DatabaseVersionPolicy;
use PDO;
use PDOException;
use RuntimeException;

final class DatabaseProvisioner
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private DatabaseDriverRegistry $drivers, private DatabaseVersionPolicy $versionPolicy)
    {
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function test(array $database, bool $create = false): array
    {
        $definition = $this->drivers->get((string) ($database['driver'] ?? 'mysql'));
        $driver = $definition;
        $nativeDriver = (string) ($definition['laravel_driver'] ?? $definition['key']);
        if (! ($driver['available'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) $driver['availability_message'],
                'driver' => $driver['key'],
                'backup_available' => false,
                'backup_message' => 'Backup is unavailable because this database driver is not usable on the server.',
            ];
        }

        try {
            $pdo = match ($nativeDriver) {
                'mysql', 'mariadb' => $this->connectMySqlFamily($database, $create && (bool) ($definition['supports_create'] ?? true)),
                'pgsql' => $this->connectPostgres($database, $create && (bool) ($definition['supports_create'] ?? true)),
                'sqlite' => $this->connectSqlite($database, $create && (bool) ($definition['supports_create'] ?? true)),
                'sqlsrv' => $this->connectSqlServer($database, $create && (bool) ($definition['supports_create'] ?? true)),
                default => throw new RuntimeException('Unsupported database driver.'),
            };

            [$version, $objects] = $this->inspect($pdo, $nativeDriver, (string) $database['database']);
            $this->versionPolicy->assertSupported((string)($driver['key'] ?? $nativeDriver), $version);
            $backup = $this->backupCapability($nativeDriver);

            return [
                'ok' => true,
                'driver' => $driver['key'],
                'driver_label' => $driver['label'],
                'version' => $version,
                'message' => sprintf('Connected successfully to %s %s. Database contains %d object(s).', $driver['label'], $version, $objects),
                'object_count' => $objects,
                'table_count' => $objects,
                'backup_available' => $backup['available'],
                'backup_message' => $backup['message'],
                'backup_strategy' => $backup['strategy'],
            ];
        } catch (PDOException|RuntimeException $exception) {
            return [
                'ok' => false,
                'driver' => $driver['key'],
                'message' => $this->safeMessage($exception),
                'backup_available' => false,
                'backup_message' => 'Backup readiness could not be determined until the connection succeeds.',
            ];
        }
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function wipe(array $database, ?callable $progress = null): void
    {
        $definition = $this->drivers->get((string) ($database['driver'] ?? 'mysql'));
        $driver = (string) ($definition['laravel_driver'] ?? $definition['key']);
        $pdo = $this->connectExisting($database);

        match ($driver) {
            'mysql', 'mariadb' => $this->wipeMySqlFamily($pdo, (string) $database['database'], $progress),
            'pgsql' => $this->wipePostgres($pdo, $progress),
            'sqlite' => $this->wipeSqlite($pdo, $progress),
            'sqlsrv' => $this->wipeSqlServer($pdo, $progress),
            default => throw new RuntimeException('Unsupported database driver.'),
        };
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function laravelConnection(array $database): array
    {
        $definition = $this->drivers->get((string) ($database['driver'] ?? 'mysql'));
        $driver = (string) ($definition['laravel_driver'] ?? $definition['key']);
        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => $this->sqlitePath((string) $database['database']),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        $connection = [
            'driver' => $driver,
            'host' => (string) ($database['host'] ?? '127.0.0.1'),
            'port' => (int) ($database['port'] ?? 0),
            'database' => (string) $database['database'],
            'username' => (string) ($database['username'] ?? ''),
            'password' => (string) ($database['password'] ?? ''),
            'prefix' => '',
            'prefix_indexes' => true,
        ];

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection += ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true, 'engine' => null];
        } elseif ($driver === 'pgsql') {
            $connection += ['charset' => 'utf8', 'search_path' => 'public', 'sslmode' => 'prefer'];
        } elseif ($driver === 'sqlsrv') {
            $connection += ['charset' => 'utf8'];
        }

        return $connection;
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function environment(array $database): array
    {
        $definition = $this->drivers->get((string) $database['driver']);
        $driver = (string) ($definition['laravel_driver'] ?? $definition['key']);
        if ($driver === 'sqlite') {
            return [
                'DB_CONNECTION' => 'sqlite',
                'DB_HOST' => '', 'DB_PORT' => '',
                'DB_DATABASE' => $this->sqlitePath((string) $database['database']),
                'DB_USERNAME' => '', 'DB_PASSWORD' => '',
                'DB_FOREIGN_KEYS' => 'true',
            ];
        }

        return [
            'DB_CONNECTION' => $driver,
            'DB_HOST' => (string) ($database['host'] ?? ''),
            'DB_PORT' => (string) ($database['port'] ?? ''),
            'DB_DATABASE' => (string) $database['database'],
            'DB_USERNAME' => (string) ($database['username'] ?? ''),
            'DB_PASSWORD' => (string) ($database['password'] ?? ''),
        ];
    }

    public function normalizeDriver(string $driver): string
    {
        try {
            $definition = $this->drivers->get($driver);
            return (string) ($definition['laravel_driver'] ?? $definition['key']);
        } catch (\InvalidArgumentException) {
            return $driver;
        }
    }

    /** @return array{available:bool,strategy:string,message:string} */
    public function backupCapability(string $driver): array
    {
        $driver = $this->normalizeDriver($driver);

        return match ($driver) {
            'mysql', 'mariadb' => ['available' => true, 'strategy' => 'native-php', 'message' => 'Nexora can create a full streaming SQL backup without an external database CLI.'],
            'sqlite' => ['available' => true, 'strategy' => 'file-copy', 'message' => 'Nexora can create an atomic SQLite database snapshot.'],
            'pgsql' => ['available' => false, 'strategy' => 'external', 'message' => 'Nexora does not execute database CLI tools from the browser installer. Use an external PostgreSQL backup if desired, or continue only with explicit destructive consent.'],
            'sqlsrv' => ['available' => false, 'strategy' => 'external', 'message' => 'Portable SQL Server backup requires server-side backup tooling/permissions. Use an external backup or continue only with explicit destructive consent.'],
            default => ['available' => false, 'strategy' => 'none', 'message' => 'Backup is not available for this driver.'],
        };
    }

    private function connectMySqlFamily(array $db, bool $create): PDO
    {
        $this->assertDatabaseName((string) $db['database']);
        $server = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'] ?? '127.0.0.1', $db['port'] ?? 3306),
            (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 6],
        );
        if ($create) {
            $name = str_replace('`', '``', (string) $db['database']);
            $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        return $this->connectExisting($db);
    }

    private function connectPostgres(array $db, bool $create): PDO
    {
        $this->assertDatabaseName((string) $db['database']);
        if ($create) {
            $maintenance = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=postgres', $db['host'] ?? '127.0.0.1', $db['port'] ?? 5432),
                (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 6],
            );
            $exists = $maintenance->prepare('SELECT 1 FROM pg_database WHERE datname = :name');
            $exists->execute(['name' => (string) $db['database']]);
            if ($exists->fetchColumn() === false) {
                $maintenance->exec('CREATE DATABASE '.$this->quotePgIdentifier((string) $db['database']));
            }
        }
        return $this->connectExisting($db);
    }

    private function connectSqlite(array $db, bool $create): PDO
    {
        $path = $this->sqlitePath((string) $db['database']);
        if (! is_file($path)) {
            if (! $create) {
                throw new RuntimeException('SQLite database file does not exist. Enable database creation or choose an existing file.');
            }
            $directory = dirname($path);
            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Unable to create the SQLite database directory.');
            }
            if (@touch($path) === false) {
                throw new RuntimeException('Unable to create the SQLite database file.');
            }
        }
        return new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 6]);
    }

    private function connectSqlServer(array $db, bool $create): PDO
    {
        $this->assertDatabaseName((string) $db['database']);
        if ($create) {
            $server = new PDO(
                sprintf('sqlsrv:Server=%s,%d;Database=master', $db['host'] ?? 'localhost', $db['port'] ?? 1433),
                (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $name = (string) $db['database'];
            $check = $server->prepare('SELECT DB_ID(?)');
            $check->execute([$name]);
            if ($check->fetchColumn() === null) {
                $server->exec('CREATE DATABASE '.$this->quoteSqlServerIdentifier($name));
            }
        }
        return $this->connectExisting($db);
    }

    private function connectExisting(array $db): PDO
    {
        $definition = $this->drivers->get((string) ($db['driver'] ?? 'mysql'));
        $driver = (string) ($definition['laravel_driver'] ?? $definition['key']);

        return match ($driver) {
            'mysql', 'mariadb' => new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'] ?? '127.0.0.1', $db['port'] ?? 3306, $db['database']),
                (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8],
            ),
            'pgsql' => new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $db['host'] ?? '127.0.0.1', $db['port'] ?? 5432, $db['database']),
                (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8],
            ),
            'sqlite' => $this->connectSqlite($db, false),
            'sqlsrv' => new PDO(
                sprintf('sqlsrv:Server=%s,%d;Database=%s', $db['host'] ?? 'localhost', $db['port'] ?? 1433, $db['database']),
                (string) ($db['username'] ?? ''), (string) ($db['password'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            ),
            default => throw new RuntimeException('Unsupported database driver.'),
        };
    }

    /** @return array{0:string,1:int} */
    private function inspect(PDO $pdo, string $driver, string $database): array
    {
        return match ($driver) {
            'mysql', 'mariadb' => [
                (string) $pdo->query('SELECT VERSION()')->fetchColumn(),
                (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn(),
            ],
            'pgsql' => [
                (string) $pdo->query('SHOW server_version')->fetchColumn(),
                (int) $pdo->query("SELECT COUNT(*) FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname=current_schema() AND c.relkind IN ('r','p','v','m','S')")->fetchColumn(),
            ],
            'sqlite' => [
                (string) $pdo->query('SELECT sqlite_version()')->fetchColumn(),
                (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type IN ('table','view','trigger') AND name NOT LIKE 'sqlite_%'")->fetchColumn(),
            ],
            'sqlsrv' => [
                trim((string) $pdo->query("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(128))")->fetchColumn()),
                (int) $pdo->query("SELECT COUNT(*) FROM sys.objects WHERE type IN ('U','V','SO') AND is_ms_shipped=0")->fetchColumn(),
            ],
            default => ['unknown', 0],
        };
    }

    private function wipeMySqlFamily(PDO $pdo, string $database, ?callable $progress): void
    {
        $statement = $pdo->prepare("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema ORDER BY CASE WHEN TABLE_TYPE = 'VIEW' THEN 0 ELSE 1 END, TABLE_NAME");
        $statement->execute(['schema' => $database]);
        $objects = $statement->fetchAll(PDO::FETCH_ASSOC);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            $this->dropObjects($pdo, $objects, $progress, static fn (array $object): string => ((string) $object['TABLE_TYPE'] === 'VIEW' ? 'DROP VIEW IF EXISTS ' : 'DROP TABLE IF EXISTS ').'`'.str_replace('`', '``', (string) $object['TABLE_NAME']).'`');
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function wipePostgres(PDO $pdo, ?callable $progress): void
    {
        $rows = $pdo->query("SELECT c.relname AS name, c.relkind AS kind FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname=current_schema() AND c.relkind IN ('r','p','v','m','S') ORDER BY CASE c.relkind WHEN 'v' THEN 0 WHEN 'm' THEN 1 WHEN 'r' THEN 2 WHEN 'p' THEN 2 ELSE 3 END, c.relname")->fetchAll(PDO::FETCH_ASSOC);
        $this->dropObjects($pdo, $rows, $progress, function (array $row): string {
            $kind = (string) $row['kind'];
            $type = match ($kind) { 'v' => 'VIEW', 'm' => 'MATERIALIZED VIEW', 'S' => 'SEQUENCE', default => 'TABLE' };
            return 'DROP '.$type.' IF EXISTS '.$this->quotePgIdentifier((string) $row['name']).' CASCADE';
        });
    }

    private function wipeSqlite(PDO $pdo, ?callable $progress): void
    {
        $rows = $pdo->query("SELECT name,type FROM sqlite_master WHERE type IN ('view','trigger','table') AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'view' THEN 0 WHEN 'trigger' THEN 1 ELSE 2 END,name")->fetchAll(PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = OFF');
        try {
            $this->dropObjects($pdo, $rows, $progress, static function (array $row): string {
                $type = strtoupper((string) $row['type']);
                $name = '"'.str_replace('"', '""', (string) $row['name']).'"';
                return 'DROP '.$type.' IF EXISTS '.$name;
            });
        } finally {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    private function wipeSqlServer(PDO $pdo, ?callable $progress): void
    {
        $foreignKeys = $pdo->query("SELECT SCHEMA_NAME(t.schema_id) AS schema_name,t.name AS table_name,fk.name AS fk_name FROM sys.foreign_keys fk JOIN sys.tables t ON t.object_id=fk.parent_object_id WHERE t.is_ms_shipped=0")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($foreignKeys as $fk) {
            $pdo->exec('ALTER TABLE '.$this->quoteSqlServerIdentifier((string) $fk['schema_name']).'.'.$this->quoteSqlServerIdentifier((string) $fk['table_name']).' DROP CONSTRAINT '.$this->quoteSqlServerIdentifier((string) $fk['fk_name']));
        }
        $rows = $pdo->query("SELECT SCHEMA_NAME(schema_id) AS schema_name,name,type FROM sys.objects WHERE type IN ('V','U','SO') AND is_ms_shipped=0 ORDER BY CASE type WHEN 'V' THEN 0 WHEN 'U' THEN 1 ELSE 2 END,name")->fetchAll(PDO::FETCH_ASSOC);
        $this->dropObjects($pdo, $rows, $progress, function (array $row): string {
            $type = match ((string) $row['type']) { 'V' => 'VIEW', 'SO' => 'SEQUENCE', default => 'TABLE' };
            return 'DROP '.$type.' '.$this->quoteSqlServerIdentifier((string) $row['schema_name']).'.'.$this->quoteSqlServerIdentifier((string) $row['name']);
        });
    }

    /** @param array<int,array<string,mixed>> $objects */
    private function dropObjects(PDO $pdo, array $objects, ?callable $progress, callable $sql): void
    {
        $total = count($objects);
        foreach ($objects as $index => $object) {
            $pdo->exec($sql($object));
            if ($progress !== null) {
                $name = (string) ($object['TABLE_NAME'] ?? $object['name'] ?? 'database object');
                $progress([
                    'current' => $index + 1, 'total' => $total, 'name' => $name,
                    'type' => strtolower((string) ($object['TABLE_TYPE'] ?? $object['type'] ?? $object['kind'] ?? 'object')),
                    'progress' => (int) floor((($index + 1) / max(1, $total)) * 100),
                ]);
            }
        }
    }

    private function sqlitePath(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return database_path('database.sqlite');
        }
        $normalized = str_replace('\\', '/', $value);
        $isAbsolute = str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1;
        return $isAbsolute ? $value : database_path($value);
    }

    private function assertDatabaseName(string $name): void
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $name) !== 1) {
            throw new RuntimeException('Database name may contain only letters, numbers and underscores.');
        }
    }

    private function quotePgIdentifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    private function quoteSqlServerIdentifier(string $name): string
    {
        return '['.str_replace(']', ']]', $name).']';
    }

    private function safeMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $message = preg_replace('/password=[^;\s]+/i', 'password=[hidden]', $message) ?? $message;
        return function_exists('mb_substr') ? mb_substr($message, 0, 500) : substr($message, 0, 500);
    }
}
