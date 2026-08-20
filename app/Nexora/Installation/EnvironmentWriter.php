<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use RuntimeException;

final class EnvironmentWriter
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private readonly AtomicFileWriter $files) {}
    /** @param array<string, string> $values */
    public function write(array $values): string
    {
        $preferred = (string) config('installer.environment_path');
        $fallback = (string) config('installer.environment_fallback_path');
        $example = (string) config('installer.environment_example_path');
        $marker = (string) config('installer.environment_marker_path');
        $target = $this->resolveTarget($preferred, $fallback);

        $contents = $this->initialContents($preferred, $fallback, $example);
        foreach ($values as $key => $value) {
            if (preg_match('/^[A-Z0-9_]+$/', $key) !== 1) {
                throw new \InvalidArgumentException("Invalid environment key [{$key}].");
            }
            $encoded = $this->encode($value);
            $pattern = '/^'.preg_quote($key, '/').'\s*=.*$/m';
            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $key.'='.$encoded, $contents, 1);
            } else {
                $contents = rtrim($contents).PHP_EOL.$key.'='.$encoded.PHP_EOL;
            }
        }

        $this->atomicWrite($target, $contents);
        $this->writeActiveMarker($marker, $target === $fallback ? 'fallback' : 'root');
        $this->invalidateCachedConfiguration();

        return $target;
    }

    private function resolveTarget(string $preferred, string $fallback): string
    {
        $preferredDir = dirname($preferred);
        if ((is_file($preferred) && is_writable($preferred)) || (! is_file($preferred) && is_dir($preferredDir) && is_writable($preferredDir))) {
            return $preferred;
        }

        $fallbackDir = dirname($fallback);
        if (! is_dir($fallbackDir) && ! @mkdir($fallbackDir, 0775, true) && ! is_dir($fallbackDir)) {
            throw new RuntimeException('Unable to prepare Nexora protected environment storage.');
        }
        if (! is_writable($fallbackDir) && ! (is_file($fallback) && is_writable($fallback))) {
            throw new RuntimeException('Neither the project .env location nor Nexora protected environment storage is writable.');
        }

        return $fallback;
    }

    private function initialContents(string $preferred, string $fallback, string $example): string
    {
        foreach ([$preferred, $fallback, $example] as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $contents = (string) file_get_contents($candidate);
                if ($contents !== '') {
                    return $contents;
                }
            }
        }

        return <<<'ENV'
APP_NAME=Nexora
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
APP_LOCALE=en
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexora
DB_USERNAME=root
DB_PASSWORD=root
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
ENV;
    }

    private function atomicWrite(string $path, string $contents): void
    {
        $this->files->write($path, $contents, 0775, 0600);
    }



    private function invalidateCachedConfiguration(): void
    {
        // Environment changes must not be shadowed by a previously generated
        // Laravel config cache on the next request/CLI process.
        $cached = base_path('bootstrap/cache/config.php');
        if (is_file($cached) && ! @unlink($cached)) {
            throw new RuntimeException('Environment was written, but stale Laravel config cache could not be removed.');
        }
    }

    private function writeActiveMarker(string $path, string $mode): void
    {
        if ($path === '') {
            return;
        }

        $this->files->write($path, $mode.PHP_EOL, 0775, 0600);
    }

    private function encode(string $value): string
    {
        if ($value === '') {
            return '""';
        }
        if (preg_match('/^[A-Za-z0-9_:\/.\-]+$/', $value) === 1 && ! in_array(strtolower($value), ['true', 'false', 'null', 'empty'], true)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', '\\n'], $value).'"';
    }
}
