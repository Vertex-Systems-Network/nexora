<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

final class SourceSetIntegrity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    /** @return array<string,mixed> */
    public function inspect(): array
    {
        $manifestPath = (string) config(
            'installer.source.manifest_path',
            base_path('bootstrap/nexora-source-manifest.json'),
        );
        $expectedManifestHash = strtolower(trim((string) config('installer.source.manifest_sha256', '')));
        $errors = [];

        if (! is_file($manifestPath) || is_link($manifestPath)) {
            return $this->failure($manifestPath, ['Critical source manifest is missing or is not a regular file.']);
        }

        $manifestHash = hash_file('sha256', $manifestPath) ?: null;
        if (preg_match('/^[a-f0-9]{64}$/', $expectedManifestHash) !== 1) {
            $errors[] = 'Expected critical source manifest SHA-256 is not configured correctly.';
        } elseif (! is_string($manifestHash) || ! hash_equals($expectedManifestHash, strtolower($manifestHash))) {
            $errors[] = sprintf(
                'Critical source manifest SHA-256 [%s] does not match package SHA-256 [%s].',
                is_string($manifestHash) ? $manifestHash : 'unavailable',
                $expectedManifestHash,
            );
        }

        try {
            $manifest = json_decode(
                (string) file_get_contents($manifestPath),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable $exception) {
            return $this->failure($manifestPath, [
                'Critical source manifest JSON is unreadable: '.$exception->getMessage(),
            ]);
        }

        if (! is_array($manifest)) {
            return $this->failure($manifestPath, ['Critical source manifest root is not an object.']);
        }

        $expectedVersion = (string) config('nexora.version', 'unknown');
        $expectedProtocol = (string) config('installer.source.expected_protocol', 'unknown');
        $expectedGeneration = (string) config('installer.source.expected_generation', 'unknown');

        foreach ([
            'platform_version' => $expectedVersion,
            'installer_protocol' => $expectedProtocol,
            'source_generation' => $expectedGeneration,
        ] as $key => $expected) {
            if (($manifest[$key] ?? null) !== $expected) {
                $errors[] = "Critical source manifest {$key} does not match the active package [{$expected}].";
            }
        }

        $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
        if ($files === []) {
            $errors[] = 'Critical source manifest does not contain any files.';
        }

        $matched = 0;
        $fileResults = [];
        $root = realpath(base_path()) ?: base_path();

        foreach ($files as $relative => $expectedHash) {
            if (! is_string($relative) || ! is_string($expectedHash)) {
                $errors[] = 'Critical source manifest contains an invalid file entry.';
                continue;
            }

            $path = base_path($relative);
            $real = realpath($path);
            $insideRoot = is_string($real)
                && ($real === $root || str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR));
            $actualHash = is_string($real) && is_file($real) && ! is_link($real)
                ? (hash_file('sha256', $real) ?: null)
                : null;
            $ok = $insideRoot
                && is_string($actualHash)
                && preg_match('/^[a-f0-9]{64}$/', strtolower($expectedHash)) === 1
                && hash_equals(strtolower($expectedHash), strtolower($actualHash));

            if ($ok) {
                $matched++;
            } else {
                $errors[] = "Critical source file mismatch [{$relative}].";
            }

            $fileResults[$relative] = [
                'ok' => $ok,
                'sha256' => $actualHash,
                'expected_sha256' => strtolower($expectedHash),
            ];
        }

        $fingerprint = is_string($manifestHash)
            ? strtolower($manifestHash)
            : null;

        return [
            'status' => $errors === [] ? 'pass' : 'fail',
            'current' => $errors === [],
            'manifest_path' => $manifestPath,
            'manifest_sha256' => $manifestHash,
            'expected_manifest_sha256' => $expectedManifestHash,
            'source_set_fingerprint' => $fingerprint,
            'file_count' => count($files),
            'matched_files' => $matched,
            'files' => $fileResults,
            'errors' => $errors,
        ];
    }

    /** @param list<string> $errors @return array<string,mixed> */
    private function failure(string $manifestPath, array $errors): array
    {
        return [
            'status' => 'fail',
            'current' => false,
            'manifest_path' => $manifestPath,
            'manifest_sha256' => null,
            'expected_manifest_sha256' => (string) config('installer.source.manifest_sha256', ''),
            'source_set_fingerprint' => null,
            'file_count' => 0,
            'matched_files' => 0,
            'files' => [],
            'errors' => $errors,
        ];
    }
}
