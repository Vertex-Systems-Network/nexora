<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Foundation\Runtime\RuntimeLimitsDoctor;

final class SystemRequirementChecker
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private readonly RuntimeLimitsDoctor $runtimeLimits) {}

    /** @return array{ready: bool, checks: array<int, array{key:string,label:string,ok:bool,detail:string,blocking:bool}>} */
    public function check(): array
    {
        $checks = [];
        $requiredPhp = (string) config('installer.required_php', '8.3.0');
        $checks[] = $this->item('php', 'PHP version', version_compare(PHP_VERSION, $requiredPhp, '>='), PHP_VERSION.' · requires '.$requiredPhp.'+', true);

        foreach ((array) config('installer.required_extensions', []) as $extension) {
            $extension = (string) $extension;
            $checks[] = $this->item('ext-'.$extension, 'PHP extension: '.$extension, extension_loaded($extension), extension_loaded($extension) ? 'Available' : 'Missing', true);
        }

        $runtime = $this->runtimeLimits->inspect();
        foreach ($runtime['checks'] as $runtimeCheck) {
            $key = (string) ($runtimeCheck['key'] ?? 'runtime');
            $checks[] = $this->item(
                'runtime-'.$key,
                'Runtime safety: '.str_replace(['.', ':', '-'], ' ', $key),
                ($runtimeCheck['status'] ?? 'fail') === 'pass',
                (string) ($runtimeCheck['detail'] ?? ''),
                true,
            );
        }

        foreach ((array) config('installer.writable_paths', []) as $path) {
            $path = (string) $path;
            $exists = is_dir($path) || @mkdir($path, 0775, true);
            $checks[] = $this->item('write-'.sha1($path), 'Writable: '.$this->relative($path), $exists && is_writable($path), $exists && is_writable($path) ? 'Ready' : 'Not writable', true);
        }

        foreach ((array) config('installer.runtime_paths', []) as $path) {
            $path = (string) $path;
            $exists = is_dir($path) || @mkdir($path, 0775, true);
            $checks[] = $this->item(
                'runtime-'.sha1($path),
                'Runtime path: '.$this->relative($path),
                $exists && is_writable($path),
                $exists && is_writable($path) ? 'Ready and writable' : 'Missing or not writable',
                true,
            );
        }

        foreach ((array) config('installer.release_files', []) as $path) {
            $path = (string) $path;
            $exists = is_file($path);
            $label = str_ends_with($path, 'vendor/autoload.php') ? 'Composer dependencies' : 'Production frontend build';
            $checks[] = $this->item('release-'.sha1($path), $label, $exists, $exists ? $this->relative($path).' ready' : $this->relative($path).' missing', true);
        }

        $envPath = (string) config('installer.environment_path');
        $fallback = (string) config('installer.environment_fallback_path');
        $rootWritable = (is_file($envPath) && is_writable($envPath)) || (! is_file($envPath) && is_writable(dirname($envPath)));
        $fallbackDir = dirname($fallback);
        $fallbackReady = (is_file($fallback) && is_writable($fallback)) || ((is_dir($fallbackDir) || @mkdir($fallbackDir, 0775, true)) && is_writable($fallbackDir));
        $envReady = $rootWritable || $fallbackReady;
        $detail = $rootWritable
            ? 'Root .env can be written atomically'
            : ($fallbackReady ? 'Protected storage fallback will be used; project root does not need to be writable' : 'No writable environment persistence location');
        $checks[] = $this->item('environment', 'Environment persistence', $envReady, $detail, true);

        return [
            'ready' => ! collect($checks)->contains(static fn (array $check): bool => $check['blocking'] && ! $check['ok']),
            'checks' => $checks,
        ];
    }

    /** @return array{key:string,label:string,ok:bool,detail:string,blocking:bool} */
    private function item(string $key, string $label, bool $ok, string $detail, bool $blocking): array
    {
        return compact('key', 'label', 'ok', 'detail', 'blocking');
    }

    private function relative(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($base))) : $path;
    }
}
