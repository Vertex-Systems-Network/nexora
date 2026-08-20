<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final class RuntimeWritableTempDirectory
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    /**
     * Resolve a writable temporary directory for installation/runtime probes.
     * Application-local storage is preferred so Windows service accounts do
     * not depend on C:\\Windows\\Temp permissions.
     *
     * @return array{
     *   status:string,
     *   selected_path:?string,
     *   system_path:string,
     *   fallback_used:bool,
     *   candidates:list<array{path:string,source:string,writable:bool,created:bool,error:?string}>
     * }
     */
    public function installation(): array
    {
        $configured = trim((string) config(
            'nexora-host-runtime.installation.temp_directory',
            '',
        ));

        $candidateMap = [];
        if ($configured !== '') {
            $candidateMap[] = [$configured, 'configured'];
        }
        $candidateMap[] = [storage_path('framework/nexora-temp'), 'app-framework'];
        $candidateMap[] = [storage_path('app/nexora/tmp'), 'app-storage'];
        $candidateMap[] = [sys_get_temp_dir(), 'php-system'];

        $seen = [];
        $results = [];
        $selected = null;
        $selectedSource = null;

        foreach ($candidateMap as [$path, $source]) {
            $normalized = $this->normalize((string) $path);
            $identity = strtolower($normalized);
            if ($normalized === '' || isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;

            $probe = $this->probeCandidate($normalized, (string) $source);
            $results[] = $probe;

            if ($selected === null && $probe['writable']) {
                $selected = $probe['path'];
                $selectedSource = $probe['source'];
            }
        }

        $system = $this->normalize(sys_get_temp_dir());

        return [
            'status' => $selected !== null ? 'pass' : 'fail',
            'selected_path' => $selected,
            'selected_source' => $selectedSource,
            'system_path' => $system,
            'fallback_used' => $selected !== null
                && strcasecmp($selected, $system) !== 0,
            'candidates' => $results,
        ];
    }

    public function systemPath(): string
    {
        return $this->normalize(sys_get_temp_dir());
    }

    /** @return array{path:string,source:string,writable:bool,created:bool,error:?string} */
    private function probeCandidate(string $path, string $source): array
    {
        $created = false;
        $error = null;

        try {
            if (file_exists($path) && ! is_dir($path)) {
                throw new \RuntimeException('path exists but is not a directory');
            }

            if (! is_dir($path)) {
                if (! @mkdir($path, 0700, true) && ! is_dir($path)) {
                    throw new \RuntimeException('directory could not be created');
                }
                $created = true;
            }

            $probe = rtrim($path, DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR.'.nexora-write-'.bin2hex(random_bytes(6));
            $bytes = @file_put_contents($probe, 'nexora-temp-probe', LOCK_EX);
            $writable = $bytes === strlen('nexora-temp-probe') && is_file($probe);
            @unlink($probe);

            if (! $writable) {
                throw new \RuntimeException('write probe failed');
            }

            return [
                'path' => $path,
                'source' => $source,
                'writable' => true,
                'created' => $created,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            $error = mb_substr($exception->getMessage(), 0, 240);
        }

        return [
            'path' => $path,
            'source' => $source,
            'writable' => false,
            'created' => $created,
            'error' => $error,
        ];
    }

    private function normalize(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
