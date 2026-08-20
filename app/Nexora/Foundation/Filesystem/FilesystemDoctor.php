<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Filesystem;

final class FilesystemDoctor
{
    public function __construct(private readonly AtomicFileWriter $files) {}

    /** @return array{status:string,checks:list<array{key:string,status:string,detail:string}>,os_family:string,directory_separator:string} */
    public function inspect(bool $probe = true): array
    {
        $checks = [];
        $failed = false;
        foreach ((array) config('nexora-filesystem.required_writable_directories', []) as $directory) {
            $directory = (string) $directory;
            $ok = $directory !== '';
            $detail = '';
            if ($ok) {
                try {
                    $this->files->ensureDirectory($directory, 0775);
                    if ($probe) {
                        $probePath = rtrim($directory, '\\/').DIRECTORY_SEPARATOR.'.nexora-fs-probe-'.bin2hex(random_bytes(5));
                        $this->files->write($probePath, 'nexora-filesystem-probe'.PHP_EOL, 0775, 0600);
                        $read = @file_get_contents($probePath);
                        if ($read !== 'nexora-filesystem-probe'.PHP_EOL) {
                            throw new \RuntimeException('Atomic write/read probe did not round-trip.');
                        }
                        if (! @unlink($probePath)) {
                            throw new \RuntimeException('Filesystem probe cleanup failed.');
                        }
                    }
                    $detail = 'ready'.($probe ? '; atomic write probe passed' : '');
                } catch (\Throwable $exception) {
                    $ok = false;
                    $detail = $exception->getMessage();
                }
            }
            $failed = $failed || ! $ok;
            $checks[] = [
                'key' => 'writable:'.$this->relative($directory),
                'status' => $ok ? 'pass' : 'fail',
                'detail' => $detail !== '' ? $detail : 'path is not configured',
            ];
        }

        foreach ((array) config('nexora-filesystem.protected_local_directories', []) as $directory) {
            $directory = (string) $directory;
            $ok = $directory !== '' && PortablePath::isLexicallyWithin(storage_path(), $directory);
            $failed = $failed || ! $ok;
            $checks[] = [
                'key' => 'protected-boundary:'.$this->relative($directory),
                'status' => $ok ? 'pass' : 'fail',
                'detail' => $ok ? 'protected local path remains under storage/' : 'protected local path escapes storage/',
            ];
        }

        return [
            'status' => $failed ? 'fail' : 'pass',
            'checks' => $checks,
            'os_family' => PHP_OS_FAMILY,
            'directory_separator' => DIRECTORY_SEPARATOR,
        ];
    }

    private function relative(string $path): string
    {
        $root = str_replace('\\', '/', rtrim(base_path(), '\\/'));
        $normalized = str_replace('\\', '/', $path);
        return $normalized === $root || str_starts_with($normalized, $root.'/')
            ? ltrim(substr($normalized, strlen($root)), '/')
            : $normalized;
    }
}
