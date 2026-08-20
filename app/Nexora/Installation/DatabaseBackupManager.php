<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Foundation\Transfers\TransferSafety;
use PDO;
use RuntimeException;

final class DatabaseBackupManager
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private DatabaseProvisioner $provisioner, private readonly AtomicFileWriter $files, private readonly TransferSafety $transfers)
    {
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function create(array $database, string $sessionId, ?callable $progress = null): array
    {
        $driver = (string) ($database['driver'] ?? 'mysql');
        $nativeDriver = $this->provisioner->normalizeDriver($driver);
        $capability = $this->provisioner->backupCapability($nativeDriver);
        if (! $capability['available']) {
            throw new RuntimeException($capability['message']);
        }

        $token = bin2hex(random_bytes(24));
        $directory = $this->directory();
        $result = match ($nativeDriver) {
            'mysql', 'mariadb' => $this->createMySqlBackup($database, $directory, $token, $progress),
            'sqlite' => $this->createSqliteBackup($database, $directory, $token, $progress),
            default => throw new RuntimeException('Nexora does not have an in-app backup strategy for this database driver.'),
        };

        $path = $result['path'];
        if (! is_file($path) || (int) filesize($path) <= 0) {
            @unlink($path);
            throw new RuntimeException('Database backup did not produce a valid file.');
        }

        $sha256=hash_file('sha256',$path);
        if(!is_string($sha256)) { @unlink($path); throw new RuntimeException('Database backup checksum could not be calculated.'); }
        $metadata = [
            'token' => $token,
            'driver' => $driver,
            'strategy' => $capability['strategy'],
            'session_hash' => hash('sha256', $sessionId),
            'database_fingerprint' => $this->fingerprint($database),
            'database' => (string) $database['database'],
            'object_count' => (int) ($result['object_count'] ?? 0),
            'bytes' => filesize($path),
            'sha256' => $sha256,
            'extension' => (string) $result['extension'],
            'content_type' => (string) $result['content_type'],
            'created_at' => gmdate(DATE_ATOM),
            'expires_at' => gmdate(DATE_ATOM, time() + 7200),
            'download_name' => 'nexora-preinstall-'.$this->safeFilename((string) $database['database']).'-'.gmdate('Ymd-His').'.'.$result['extension'],
        ];

        $this->writeMetadata($token, $metadata);
        $this->emit($progress, ['type' => 'backup_complete', 'progress' => 100, 'message' => 'Database backup is ready to download.']);

        return $metadata;
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $database */
    public function validate(string $token, array $database, string $sessionId): array
    {
        $metadata = $this->metadata($token);
        $path = $this->backupPath($token, (string) ($metadata['extension'] ?? 'sql'));
        if (! is_file($path)) {
            throw new RuntimeException('The database backup is missing or has expired. Create a new backup before continuing.');
        }
        $this->assertBackupIntegrity($path,$metadata);
        if (! hash_equals((string) ($metadata['session_hash'] ?? ''), hash('sha256', $sessionId))) {
            throw new RuntimeException('This database backup belongs to another installer session.');
        }
        if (! hash_equals((string) ($metadata['database_fingerprint'] ?? ''), $this->fingerprint($database))) {
            throw new RuntimeException('Database settings changed after the backup. Test the connection and create a new backup.');
        }
        if (strtotime((string) ($metadata['expires_at'] ?? '1970-01-01')) < time()) {
            $this->remove($token);
            throw new RuntimeException('The database backup expired. Create and download a new backup.');
        }
        if (empty($metadata['downloaded_at'])) {
            throw new RuntimeException('Download the protected backup before authorizing a database reset, or explicitly choose to continue without a backup.');
        }

        return $metadata;
    }

    public function file(string $token, string $sessionId): array
    {
        $metadata = $this->metadata($token);
        if (! hash_equals((string) ($metadata['session_hash'] ?? ''), hash('sha256', $sessionId))) {
            throw new RuntimeException('This database backup belongs to another installer session.');
        }
        $extension = (string) ($metadata['extension'] ?? 'sql');
        $path = $this->backupPath($token, $extension);
        if (! is_file($path)) {
            throw new RuntimeException('The requested database backup is unavailable.');
        }
        $this->assertBackupIntegrity($path,$metadata);

        $metadata['downloaded_at'] = gmdate(DATE_ATOM);
        $this->writeMetadata($token, $metadata);

        return [
            'path' => $path,
            'name' => (string) ($metadata['download_name'] ?? 'nexora-database-backup.'.$extension),
            'content_type' => (string) ($metadata['content_type'] ?? 'application/octet-stream'),
        ];
    }

    public function remove(string $token): void
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) {
            return;
        }
        $metadata = null;
        try { $metadata = $this->metadata($token); } catch (\Throwable) {}
        if (is_array($metadata)) {
            @unlink($this->backupPath($token, (string) ($metadata['extension'] ?? 'sql')));
        }
        @unlink($this->directory().'/'.$token.'.json');
    }

    private function createMySqlBackup(array $database, string $directory, string $token, ?callable $progress): array
    {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $database['host'] ?? '127.0.0.1', $database['port'] ?? 3306, $database['database']),
            (string) ($database['username'] ?? ''), (string) ($database['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false],
        );
        $objects = $this->mysqlObjects($pdo, (string) $database['database']);
        if ($objects === []) throw new RuntimeException('The selected database is empty; no backup is required.');
        $path = $this->backupPath($token, 'sql');
        $staging=$this->transfers->siblingTemporaryPath($path,'database-backup');
        $this->transfers->assertLocalCapacity($path,0,(int)config('nexora-transfers.backup.minimum_free_bytes',268_435_456));
        $handle = @fopen($staging, 'xb');
        if ($handle === false) throw new RuntimeException('Nexora could not create the protected database backup staging file.');
        $total = count($objects);
        try {
            $this->write($handle, "-- Nexora pre-install database backup\n-- Driver: ".($database['driver'] ?? 'mysql')."\n-- Created: ".gmdate(DATE_ATOM)."\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            foreach ($objects as $index => $object) {
                $position = $index + 1;
                $baseProgress = (int) floor((($position - 1) / max(1, $total)) * 96);
                $this->emit($progress, ['type' => 'backup_step', 'progress' => max(1, $baseProgress), 'object' => $object['name'], 'object_type' => $object['type'], 'message' => sprintf('Backing up %s %d of %d: %s', strtolower($object['type']), $position, $total, $object['name'])]);
                if ($object['type'] === 'VIEW') {
                    $this->dumpMySqlView($pdo, $handle, $object['name']);
                } else {
                    $this->dumpMySqlTable($pdo, $handle, $object['name'], $object['rows'], function (int $rowsDone, int $estimatedRows) use ($progress, $baseProgress, $total, $object): void {
                        if ($estimatedRows <= 0) return;
                        $objectShare = 96 / max(1, $total);
                        $fraction = min(1, $rowsDone / max(1, $estimatedRows));
                        $this->emit($progress, ['type' => 'backup_progress', 'progress' => min(96, (int) floor($baseProgress + ($objectShare * $fraction))), 'object' => $object['name'], 'rows' => $rowsDone, 'message' => number_format($rowsDone).' row(s) exported from '.$object['name']]);
                    });
                }
            }
            $this->write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            if (! @fflush($handle)) throw new RuntimeException('Unable to flush the database backup staging file.');
            if (function_exists('fsync') && ! @fsync($handle)) throw new RuntimeException('Unable to sync the database backup staging file.');
        } catch (\Throwable $e) {
            @unlink($staging);
            throw $e;
        } finally { fclose($handle); }
        try { $this->files->moveVerified($staging,$path,0700); }
        finally { if (is_file($staging)) @unlink($staging); }
        return ['path' => $path, 'extension' => 'sql', 'content_type' => 'application/sql', 'object_count' => $total];
    }

    private function createSqliteBackup(array $database, string $directory, string $token, ?callable $progress): array
    {
        $source = $this->sqlitePath((string) $database['database']);
        if (! is_file($source)) throw new RuntimeException('SQLite database file does not exist.');
        $maximum=(int)config('nexora-transfers.backup.max_bytes',53_687_091_200);
        $sourceSize=$this->transfers->assertSourceFile($source,$maximum,'SQLite database');
        $path = $this->backupPath($token, 'sqlite');
        $staging=$this->transfers->siblingTemporaryPath($path,'sqlite-backup');
        $this->transfers->assertLocalCapacity($path,$sourceSize,(int)config('nexora-transfers.backup.minimum_free_bytes',268_435_456));
        $this->emit($progress, ['type' => 'backup_step', 'progress' => 10, 'message' => 'Opening SQLite database for an atomic snapshot.']);
        $pdo = new PDO('sqlite:'.$source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $quoted = str_replace("'", "''", $staging);
        try {
            try { $pdo->exec("VACUUM INTO '{$quoted}'"); }
            catch (\Throwable) { $this->transfers->copyFileAtomically($source,$staging,$maximum,0700); }
            $this->transfers->assertSourceFile($staging,$maximum,'SQLite backup snapshot');
            $this->files->moveVerified($staging,$path,0700);
        } catch (\Throwable $e) {
            @unlink($staging); @unlink($path);
            throw new RuntimeException('Unable to create the SQLite backup snapshot: '.$e->getMessage(),0,$e);
        } finally { if (is_file($staging)) @unlink($staging); }
        $count = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type IN ('table','view','trigger') AND name NOT LIKE 'sqlite_%'")->fetchColumn();
        $this->emit($progress, ['type' => 'backup_progress', 'progress' => 95, 'message' => 'SQLite snapshot written and verified.']);
        return ['path' => $path, 'extension' => 'sqlite', 'content_type' => 'application/vnd.sqlite3', 'object_count' => $count];
    }

    /** @return array<int,array{name:string,type:string,rows:int}> */
    private function mysqlObjects(PDO $pdo, string $database): array
    {
        $statement = $pdo->prepare("SELECT TABLE_NAME, TABLE_TYPE, COALESCE(TABLE_ROWS,0) AS TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA=:schema ORDER BY CASE WHEN TABLE_TYPE='BASE TABLE' THEN 0 ELSE 1 END,TABLE_NAME");
        $statement->execute(['schema' => $database]);
        return array_map(static fn (array $row): array => ['name' => (string) $row['TABLE_NAME'], 'type' => (string) $row['TABLE_TYPE'] === 'VIEW' ? 'VIEW' : 'TABLE', 'rows' => max(0, (int) $row['TABLE_ROWS'])], $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    private function dumpMySqlTable(PDO $pdo, $handle, string $table, int $estimatedRows, ?callable $progress): void
    {
        $quoted = '`'.str_replace('`', '``', $table).'`';
        $create = $pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_ASSOC);
        $createSql = is_array($create) ? (string) (array_values($create)[1] ?? '') : '';
        if ($createSql === '') throw new RuntimeException('Could not read schema for table '.$table.'.');
        $this->write($handle, "-- Table: {$quoted}\nDROP TABLE IF EXISTS {$quoted};\n{$createSql};\n\n");
        $query = $pdo->query('SELECT * FROM '.$quoted);
        $count = 0;
        while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
            $columns = array_map(static fn (string $column): string => '`'.str_replace('`', '``', $column).'`', array_keys($row));
            $values = array_map(static fn ($value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row));
            $this->write($handle, 'INSERT INTO '.$quoted.' ('.implode(',', $columns).') VALUES ('.implode(',', $values).");\n");
            $count++;
            if ($progress !== null && $count % 500 === 0) $progress($count, max($estimatedRows, $count));
        }
        if ($progress !== null) $progress($count, max($estimatedRows, $count, 1));
        $this->write($handle, "\n");
    }

    private function dumpMySqlView(PDO $pdo, $handle, string $view): void
    {
        $quoted = '`'.str_replace('`', '``', $view).'`';
        $create = $pdo->query('SHOW CREATE VIEW '.$quoted)->fetch(PDO::FETCH_ASSOC);
        $createSql = is_array($create) ? (string) ($create['Create View'] ?? array_values($create)[1] ?? '') : '';
        if ($createSql === '') throw new RuntimeException('Could not read definition for view '.$view.'.');
        $this->write($handle, "-- View: {$quoted}\nDROP VIEW IF EXISTS {$quoted};\n{$createSql};\n\n");
    }

    /** @return array<string,mixed> */
    private function metadata(string $token): array
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1) throw new RuntimeException('Invalid database backup token.');
        $path = $this->directory().'/'.$token.'.json';
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($decoded)) throw new RuntimeException('Database backup metadata is unavailable.');
        return $decoded;
    }

    /** @param array<string,mixed> $metadata */
    private function writeMetadata(string $token, array $metadata): void
    {
        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->files->write($this->directory().'/'.$token.'.json', $json, 0700, 0600);
    }

    private function backupPath(string $token, string $extension): string
    {
        if (preg_match('/^[a-f0-9]{48}$/', $token) !== 1 || preg_match('/^[a-z0-9]+$/', $extension) !== 1) throw new RuntimeException('Invalid backup path.');
        return $this->directory().'/'.$token.'.'.$extension;
    }

    private function directory(): string
    {
        $directory = base_path('storage/app/nexora/database-backups');
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) throw new RuntimeException('Unable to prepare protected database backup storage.');
        return $directory;
    }

    private function write($handle, string $contents): void
    {
        $offset=0;$length=strlen($contents);
        while($offset<$length){
            $written=fwrite($handle,substr($contents,$offset));
            if($written===false||$written===0) throw new RuntimeException('Writing the database backup failed; the destination may be full or unavailable.');
            $offset+=$written;
        }
        $position=ftell($handle);
        $maximum=(int)config('nexora-transfers.backup.max_bytes',53_687_091_200);
        if(is_int($position)&&$maximum>0&&$position>$maximum) throw new RuntimeException('Database backup exceeded the configured maximum size. Use external backup tooling for larger databases.');
    }

    /** @param array<string,mixed> $metadata */
    private function assertBackupIntegrity(string $path,array $metadata): void
    {
        $maximum=(int)config('nexora-transfers.backup.max_bytes',53_687_091_200);
        $size=$this->transfers->assertSourceFile($path,$maximum,'Database backup');
        if(isset($metadata['bytes']) && (int)$metadata['bytes']!==$size) throw new RuntimeException('Database backup byte count no longer matches its sealed metadata.');
        $expected=(string)($metadata['sha256']??'');
        if($expected!==''){
            $actual=hash_file('sha256',$path);
            if(!is_string($actual)||!hash_equals($expected,$actual)) throw new RuntimeException('Database backup checksum no longer matches its sealed metadata.');
        }
    }

    private function sqlitePath(string $value): string
    {
        $value = trim($value);
        $normalized = str_replace('\\', '/', $value);
        $absolute = str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1;
        return $absolute ? $value : database_path($value === '' ? 'database.sqlite' : $value);
    }

    private function fingerprint(array $database): string
    {
        return hash('sha256', strtolower((string) ($database['driver'] ?? 'mysql')).'|'.strtolower((string) ($database['host'] ?? '')).'|'.(string) ($database['port'] ?? '').'|'.(string) $database['database'].'|'.(string) ($database['username'] ?? ''));
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?: 'database';
    }

    private function emit(?callable $progress, array $event): void
    {
        if ($progress !== null) $progress($event);
    }

}
