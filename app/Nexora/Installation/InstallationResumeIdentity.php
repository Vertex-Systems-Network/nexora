<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use RuntimeException;

final class InstallationResumeIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private ?array $memoized = null;

    public function __construct(
        private readonly SourceSetIntegrity $sourceSet,
    ) {
    }

    /** @return array<string,mixed> */
    public function current(): array
    {
        if ($this->memoized !== null) {
            return $this->memoized;
        }

        $source = $this->fullSourceAttestation();
        $critical = $this->sourceSet->inspect();
        if (($critical['status'] ?? 'fail') !== 'pass') {
            throw new RuntimeException(
                'Installation resume provenance cannot be calculated because the critical source set is not current.',
            );
        }

        $materials = [
            'schema' => 2,
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'installer_protocol' => Installer::PROTOCOL,
            'source_generation' => Installer::SOURCE_GENERATION,
            'source_tree_sha256' => $source['tree_sha256'],
            'source_tree_file_count' => $source['file_count'],
            'critical_source_manifest_sha256' => $critical['manifest_sha256'] ?? null,
            'critical_source_file_count' => (int) ($critical['file_count'] ?? 0),
            'migrations_sha256' => $this->directoryManifest(database_path('migrations'), '.php'),
            'core_seeders_sha256' => $this->directoryManifest(database_path('seeders/Core'), '.php'),
            'composer_lock_sha256' => $this->fileHash(base_path('composer.lock')),
            'package_lock_sha256' => $this->fileHash(base_path('package-lock.json')),
        ];

        return $this->memoized = [
            'fingerprint' => $this->hash($materials),
            'materials' => $materials,
        ];
    }

    public function fingerprint(): string
    {
        return (string) $this->current()['fingerprint'];
    }

    /** @return array{tree_sha256:string,file_count:int} */
    private function fullSourceAttestation(): array
    {
        require_once base_path('scripts/lib/source-attestation.php');

        $attestation = \nexoraComputeSourceAttestation(base_path());
        $tree = strtolower(trim((string) ($attestation['tree_sha256'] ?? '')));
        $fileCount = (int) ($attestation['file_count'] ?? 0);

        if (preg_match('/^[a-f0-9]{64}$/', $tree) !== 1 || $fileCount < 1) {
            throw new RuntimeException('Unable to calculate exact full-source installation resume provenance.');
        }

        return [
            'tree_sha256' => $tree,
            'file_count' => $fileCount,
        ];
    }

    private function directoryManifest(string $directory, string $suffix): string
    {
        $items = [];

        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), $suffix)) {
                    continue;
                }

                $path = $file->getPathname();
                $relative = str_replace('\\', '/', substr($path, strlen($directory) + 1));
                $items[$relative] = $this->fileHash($path);
            }
        }

        ksort($items, SORT_STRING);

        return $this->hash($items);
    }

    private function fileHash(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) ? $hash : null;
    }

    private function hash(mixed $value): string
    {
        return hash(
            'sha256',
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }
}
