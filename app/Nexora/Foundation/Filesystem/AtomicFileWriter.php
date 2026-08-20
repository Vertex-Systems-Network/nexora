<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Filesystem;

use RuntimeException;

final class AtomicFileWriter
{
    public function ensureDirectory(string $directory, int $mode = 0755): void
    {
        if (! is_dir($directory) && ! @mkdir($directory, $mode, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create filesystem directory [{$directory}].");
        }
        if (! is_writable($directory)) {
            throw new RuntimeException("Filesystem directory is not writable [{$directory}].");
        }
    }

    public function write(string $path, string $contents, int $directoryMode = 0755, ?int $fileMode = null): void
    {
        $directory = dirname($path);
        $this->ensureDirectory($directory, $directoryMode);
        if (is_link($path)) {
            throw new RuntimeException("Refusing to replace symbolic-link state file [{$path}].");
        }

        $temporary = $directory.DIRECTORY_SEPARATOR.'.nexora-atomic-'.bin2hex(random_bytes(8)).'.tmp';
        $handle = @fopen($temporary, 'xb');
        if (! is_resource($handle)) {
            throw new RuntimeException("Unable to create atomic temporary file [{$temporary}].");
        }

        try {
            if (! @flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the atomic temporary file.');
            }
            $this->writeAll($handle, $contents);
            if (! @fflush($handle)) {
                throw new RuntimeException('Unable to flush the atomic temporary file.');
            }
            if (function_exists('fsync') && ! @fsync($handle)) {
                throw new RuntimeException('Unable to sync the atomic temporary file to storage.');
            }
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }

        if ($fileMode !== null) {
            @chmod($temporary, $fileMode);
        }

        try {
            $this->replaceTemporary($temporary, $path);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * Move a file to a destination while preserving atomic publication at the destination.
     * If rename() cannot cross a filesystem boundary, the source is copied into a
     * destination-local temporary file, verified by size + SHA-256, then published.
     */
    public function moveVerified(string $source, string $destination, int $directoryMode = 0755): void
    {
        if (! is_file($source) || is_link($source)) {
            throw new RuntimeException('Atomic move source must be a regular non-symlink file.');
        }
        $directory = dirname($destination);
        $this->ensureDirectory($directory, $directoryMode);
        if (is_link($destination)) {
            throw new RuntimeException('Refusing to replace a symbolic-link destination.');
        }
        if (file_exists($destination) && ! is_file($destination)) {
            throw new RuntimeException('Atomic move destination must be a regular file path.');
        }

        if (! is_file($destination) && @rename($source, $destination)) {
            return;
        }

        $temporary = $directory.DIRECTORY_SEPARATOR.'.nexora-move-'.bin2hex(random_bytes(8)).'.tmp';
        try {
            $input = @fopen($source, 'rb');
            $output = @fopen($temporary, 'xb');
            if (! is_resource($input) || ! is_resource($output)) {
                if (is_resource($input)) fclose($input);
                if (is_resource($output)) fclose($output);
                throw new RuntimeException('Unable to open a filesystem move stream.');
            }
            try {
                if (stream_copy_to_stream($input, $output) === false || ! @fflush($output)) {
                    throw new RuntimeException('Unable to copy filesystem move payload.');
                }
                if (function_exists('fsync') && ! @fsync($output)) {
                    throw new RuntimeException('Unable to sync filesystem move payload.');
                }
            } finally {
                fclose($input);
                fclose($output);
            }

            $sourceSize = @filesize($source);
            $tempSize = @filesize($temporary);
            $sourceHash = @hash_file('sha256', $source);
            $tempHash = @hash_file('sha256', $temporary);
            if ($sourceSize === false || $tempSize === false || $sourceSize !== $tempSize || ! is_string($sourceHash) || ! is_string($tempHash) || ! hash_equals($sourceHash, $tempHash)) {
                throw new RuntimeException('Cross-filesystem move verification failed.');
            }

            $this->replaceTemporary($temporary, $destination);
            if (! @unlink($source)) {
                throw new RuntimeException('Destination was published, but source cleanup failed after verified move.');
            }
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    /** @param resource $handle */
    private function writeAll($handle, string $contents): void
    {
        $length = strlen($contents);
        $offset = 0;
        while ($offset < $length) {
            $written = fwrite($handle, substr($contents, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to persist complete atomic file payload.');
            }
            $offset += $written;
        }
    }

    private function replaceTemporary(string $temporary, string $path): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            if (! @rename($temporary, $path)) {
                throw new RuntimeException("Unable to publish atomic file [{$path}].");
            }
            return;
        }

        if (! file_exists($path)) {
            if (! @rename($temporary, $path)) {
                throw new RuntimeException("Unable to publish atomic file [{$path}] on Windows.");
            }
            return;
        }

        $backup = dirname($path).DIRECTORY_SEPARATOR.'.nexora-replace-'.bin2hex(random_bytes(8)).'.bak';
        if (! @rename($path, $backup)) {
            throw new RuntimeException("Unable to stage existing atomic file [{$path}] for replacement on Windows.");
        }
        try {
            if (! @rename($temporary, $path)) {
                @rename($backup, $path);
                throw new RuntimeException("Unable to publish atomic file [{$path}] on Windows.");
            }
            if (! @unlink($backup) && is_file($backup)) {
                throw new RuntimeException("Atomic file [{$path}] was published but its Windows replacement backup could not be removed.");
            }
        } catch (\Throwable $exception) {
            if (! is_file($path) && is_file($backup)) @rename($backup, $path);
            throw $exception;
        }
    }
}
