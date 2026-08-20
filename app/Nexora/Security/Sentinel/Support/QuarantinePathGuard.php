<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

final class QuarantinePathGuard
{
    public function assertInside(string $path): string
    {
        $root = (string) config('sentinel.quarantine_path');
        $realRoot = realpath($root);
        $realPath = realpath($path);

        if ($realRoot === false || $realPath === false) {
            throw new \RuntimeException('Sentinel could not resolve the quarantine filesystem boundary.');
        }

        $rootNormalized = rtrim(str_replace('\\', '/', $realRoot), '/').'/';
        $pathNormalized = str_replace('\\', '/', $realPath);

        if (PHP_OS_FAMILY === 'Windows') {
            $rootNormalized = strtolower($rootNormalized);
            $pathNormalized = strtolower($pathNormalized);
        }

        if (! str_starts_with($pathNormalized, $rootNormalized)) {
            throw new \RuntimeException('Sentinel refused a package path outside the configured quarantine boundary.');
        }

        return $realPath;
    }
}
