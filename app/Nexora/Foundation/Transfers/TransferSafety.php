<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Transfers;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use RuntimeException;
use ZipArchive;

final class TransferSafety
{
    public function __construct(private readonly AtomicFileWriter $files) {}

    public function temporaryRoot(): string
    {
        $root = (string) config('nexora-transfers.temporary_root', storage_path('app/nexora/transfers'));
        if ($root === '') throw new RuntimeException('Nexora transfer temporary root is not configured.');
        $this->files->ensureDirectory($root, 0700);
        return $root;
    }

    public function temporaryPath(string $prefix = 'transfer', string $extension = '.tmp'): string
    {
        $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '-', $prefix) ?: 'transfer';
        $extension = preg_match('/^\.[A-Za-z0-9]+$/', $extension) === 1 ? $extension : '.tmp';
        return $this->temporaryRoot().DIRECTORY_SEPARATOR.$prefix.'-'.bin2hex(random_bytes(10)).$extension;
    }

    public function siblingTemporaryPath(string $destination, string $label = 'part'): string
    {
        $directory = dirname($destination);
        $this->files->ensureDirectory($directory, 0700);
        return $directory.DIRECTORY_SEPARATOR.'.nexora-'.$label.'-'.bin2hex(random_bytes(10)).'.part';
    }

    public function assertSourceFile(string $path, int $maximumBytes, string $label): int
    {
        if (! is_file($path) || is_link($path) || ! is_readable($path)) {
            throw new RuntimeException("{$label} must be a readable regular file.");
        }
        $size = @filesize($path);
        if (! is_int($size) || $size < 1) throw new RuntimeException("{$label} is empty or its size cannot be determined.");
        if ($maximumBytes > 0 && $size > $maximumBytes) throw new RuntimeException("{$label} exceeds the configured ".self::formatBytes($maximumBytes).' transfer limit.');
        return $size;
    }

    public function assertLocalCapacity(string $path, int $expectedBytes = 0, ?int $reserveBytes = null): void
    {
        $directory = is_dir($path) ? $path : dirname($path);
        while (! is_dir($directory) && dirname($directory) !== $directory) $directory = dirname($directory);
        if (! is_dir($directory)) throw new RuntimeException('Unable to resolve a local filesystem for transfer capacity checks.');
        $free = @disk_free_space($directory);
        if (! is_float($free) && ! is_int($free)) return; // Some network/virtual filesystems do not expose capacity.
        $reserve = $reserveBytes ?? (int) config('nexora-transfers.minimum_free_bytes', 67_108_864);
        $required = max(0, $expectedBytes) + max(0, $reserve);
        if ((float) $free < $required) {
            throw new RuntimeException('Insufficient local disk space for the transfer. Required free capacity is approximately '.self::formatBytes($required).'.');
        }
    }

    /** @param resource $input @param resource $output @return array{bytes:int,sha256:string} */
    public function copyStream($input, $output, int $maximumBytes, ?int $expectedBytes = null): array
    {
        if (! is_resource($input) || ! is_resource($output)) throw new RuntimeException('Transfer streams are not available.');
        if ($expectedBytes !== null && ($expectedBytes < 0 || ($maximumBytes > 0 && $expectedBytes > $maximumBytes))) {
            throw new RuntimeException('Transfer payload exceeds the configured stream budget.');
        }
        $chunk = max(16_384, min(4_194_304, (int) config('nexora-transfers.stream_chunk_bytes', 1_048_576)));
        $hash = hash_init('sha256');
        $bytes = 0;
        while (! feof($input)) {
            $buffer = fread($input, $chunk);
            if ($buffer === false) throw new RuntimeException('Reading the transfer stream failed.');
            if ($buffer === '') { if (feof($input)) break; continue; }
            $bytes += strlen($buffer);
            if ($maximumBytes > 0 && $bytes > $maximumBytes) throw new RuntimeException('Transfer payload exceeded the configured stream budget while copying.');
            hash_update($hash, $buffer);
            $offset = 0; $length = strlen($buffer);
            while ($offset < $length) {
                $written = fwrite($output, substr($buffer, $offset));
                if ($written === false || $written === 0) throw new RuntimeException('Writing the transfer stream failed; the destination may be full or unavailable.');
                $offset += $written;
            }
        }
        if ($expectedBytes !== null && $bytes !== $expectedBytes) throw new RuntimeException("Transfer byte count mismatch: expected {$expectedBytes}, copied {$bytes}.");
        return ['bytes' => $bytes, 'sha256' => hash_final($hash)];
    }

    /** @param resource $input @return array{bytes:int,sha256:string} */
    public function hashStream($input, int $maximumBytes = 0): array
    {
        if (! is_resource($input)) throw new RuntimeException('Transfer hash stream is not available.');
        $chunk = max(16_384, min(4_194_304, (int) config('nexora-transfers.stream_chunk_bytes', 1_048_576)));
        $hash = hash_init('sha256'); $bytes = 0;
        while (! feof($input)) {
            $buffer = fread($input, $chunk);
            if ($buffer === false) throw new RuntimeException('Reading the transfer hash stream failed.');
            if ($buffer === '') { if (feof($input)) break; continue; }
            $bytes += strlen($buffer);
            if ($maximumBytes > 0 && $bytes > $maximumBytes) throw new RuntimeException('Transfer hash stream exceeded the configured budget.');
            hash_update($hash, $buffer);
        }
        return ['bytes'=>$bytes,'sha256'=>hash_final($hash)];
    }

    /** @return array{bytes:int,sha256:string} */
    public function copyFileAtomically(string $source, string $destination, int $maximumBytes, int $directoryMode = 0700): array
    {
        $size = $this->assertSourceFile($source, $maximumBytes, 'Transfer source');
        $this->assertLocalCapacity($destination, $size);
        $temporary = $this->siblingTemporaryPath($destination, 'stream');
        $input = @fopen($source, 'rb');
        $output = @fopen($temporary, 'xb');
        if (! is_resource($input) || ! is_resource($output)) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            @unlink($temporary);
            throw new RuntimeException('Unable to open atomic transfer streams.');
        }
        try {
            $result = $this->copyStream($input, $output, $maximumBytes, $size);
            if (! @fflush($output)) throw new RuntimeException('Unable to flush the transferred file.');
            if (function_exists('fsync') && ! @fsync($output)) throw new RuntimeException('Unable to sync the transferred file.');
        } catch (\Throwable $e) {
            @unlink($temporary);
            throw $e;
        } finally {
            fclose($input); fclose($output);
        }
        try { $this->files->moveVerified($temporary, $destination, $directoryMode); }
        finally { if (is_file($temporary)) @unlink($temporary); }
        return $result;
    }

    /** @param resource $input @return array{bytes:int,sha256:string} */
    public function copyStreamAtomically($input, string $destination, int $maximumBytes, ?int $expectedBytes = null, int $directoryMode = 0700): array
    {
        $this->assertLocalCapacity($destination, $expectedBytes ?? 0);
        $temporary = $this->siblingTemporaryPath($destination, 'stream');
        $output = @fopen($temporary, 'xb');
        if (! is_resource($output)) throw new RuntimeException('Unable to create a bounded transfer staging file.');
        try {
            $result = $this->copyStream($input, $output, $maximumBytes, $expectedBytes);
            if (! @fflush($output)) throw new RuntimeException('Unable to flush the bounded transfer staging file.');
            if (function_exists('fsync') && ! @fsync($output)) throw new RuntimeException('Unable to sync the bounded transfer staging file.');
        } catch (\Throwable $e) {
            @unlink($temporary);
            throw $e;
        } finally { fclose($output); }
        try { $this->files->moveVerified($temporary, $destination, $directoryMode); }
        finally { if (is_file($temporary)) @unlink($temporary); }
        return $result;
    }

    /** @param array<string,mixed> $budget @return array{entries:int,total_uncompressed_bytes:int,max_entry_bytes:int} */
    public function assertArchiveBudget(ZipArchive $zip, array $budget, string $label): array
    {
        $maxEntries = max(1, (int) ($budget['max_entries'] ?? 1000));
        $maxTotal = max(1, (int) ($budget['max_total_uncompressed_bytes'] ?? 134_217_728));
        $maxEntry = max(1, (int) ($budget['max_entry_uncompressed_bytes'] ?? 20_971_520));
        $maxRatio = max(1, (int) ($budget['max_compression_ratio'] ?? 100));
        if ($zip->numFiles > $maxEntries) throw new RuntimeException("{$label} archive contains too many entries ({$zip->numFiles}; limit {$maxEntries}).");
        $total = 0; $largest = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (! is_array($stat)) throw new RuntimeException("Unable to inspect {$label} archive entry {$i}.");
            $name = (string) ($stat['name'] ?? '');
            if ($name === '' || str_ends_with(str_replace('\\', '/', $name), '/')) continue;
            $size = max(0, (int) ($stat['size'] ?? 0));
            $compressed = max(0, (int) ($stat['comp_size'] ?? 0));
            if ($size > $maxEntry) throw new RuntimeException("{$label} archive entry [{$name}] exceeds the per-entry uncompressed limit.");
            $total += $size; $largest = max($largest, $size);
            if ($total > $maxTotal) throw new RuntimeException("{$label} archive exceeds the total uncompressed transfer budget.");
            if ($size > 1_048_576 && $compressed > 0 && ($size / $compressed) > $maxRatio) throw new RuntimeException("{$label} archive entry [{$name}] exceeds the allowed compression ratio.");
        }
        return ['entries' => $zip->numFiles, 'total_uncompressed_bytes' => $total, 'max_entry_bytes' => $largest];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) return number_format($bytes / 1_073_741_824, 1).' GiB';
        if ($bytes >= 1_048_576) return number_format($bytes / 1_048_576, 1).' MiB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1).' KiB';
        return $bytes.' B';
    }
}
