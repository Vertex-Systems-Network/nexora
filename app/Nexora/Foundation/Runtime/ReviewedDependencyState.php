<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final class ReviewedDependencyState
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public const REVIEW_PATH = 'storage/app/nexora/dependency-intake/reviewed-locks.json';

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $hashes = $this->currentHashes();
        $lockedLaravelVersion = $this->lockedLaravelVersion();
        $identityErrors = $this->identityErrors($hashes, $lockedLaravelVersion);
        $review = $this->readReview();
        $reviewErrors = $this->reviewErrors(
            review: $review,
            hashes: $hashes,
            lockedLaravelVersion: $lockedLaravelVersion,
        );

        $reviewStatus = $this->reviewStatus($review, $reviewErrors);
        $runtimeErrors = $identityErrors;

        // A missing review is allowed for a fresh-install bootstrap only. Once a
        // review file exists, an unreadable/stale/invalid review is a hard runtime
        // identity failure rather than an excuse to silently fall back.
        if (($review['state'] ?? 'missing') !== 'missing') {
            $runtimeErrors = array_merge($runtimeErrors, $reviewErrors);
        }

        $fingerprintMaterials = array_merge($hashes, [
            'laravel_framework_locked_version' => $lockedLaravelVersion,
        ]);

        return [
            'status' => $identityErrors === [] && $reviewErrors === [] ? 'pass' : 'fail',
            'runtime_status' => $runtimeErrors === [] ? 'pass' : 'fail',
            'review_status' => $reviewStatus,
            'review_required' => $reviewStatus !== 'reviewed',
            'fingerprint' => $this->fingerprint($fingerprintMaterials),
            'hashes' => $hashes,
            'laravel_framework_locked_version' => $lockedLaravelVersion,
            'review' => $review['data'] ?? null,
            'review_path' => self::REVIEW_PATH,
            'identity_errors' => $identityErrors,
            'review_errors' => $reviewErrors,
            'errors' => array_values(array_unique(array_merge($identityErrors, $reviewErrors))),
        ];
    }

    /** @return array<string, string|null> */
    public function currentHashes(): array
    {
        return [
            'composer_manifest_sha256' => $this->hashFile(base_path('composer.json')),
            'package_manifest_sha256' => $this->hashFile(base_path('package.json')),
            'composer_lock_sha256' => $this->hashFile(base_path('composer.lock')),
            'package_lock_sha256' => $this->hashFile(base_path('package-lock.json')),
        ];
    }

    public function lockedLaravelVersion(): ?string
    {
        $lock = $this->decodeJsonFile(base_path('composer.lock'));
        if ($lock === null) {
            return null;
        }

        foreach (['packages', 'packages-dev'] as $bucket) {
            foreach ((array) ($lock[$bucket] ?? []) as $package) {
                if (($package['name'] ?? null) !== 'laravel/framework') {
                    continue;
                }

                $version = $package['version'] ?? null;

                return is_string($version)
                    ? ltrim(trim($version), 'v')
                    : null;
            }
        }

        return null;
    }

    public function reviewFileHash(): ?string
    {
        return $this->hashFile(base_path(self::REVIEW_PATH));
    }

    /** @param array<string, string|null> $hashes @return list<string> */
    private function identityErrors(array $hashes, ?string $lockedLaravelVersion): array
    {
        $errors = [];

        foreach ($hashes as $key => $hash) {
            if ($hash === null) {
                $errors[] = "Required dependency identity artifact is missing or unreadable [{$key}].";
            }
        }

        $composerLock = $this->decodeJsonFile(base_path('composer.lock'));
        if ($composerLock === null) {
            $errors[] = 'composer.lock is missing or invalid.';
        } else {
            if (! is_string($composerLock['content-hash'] ?? null) || $composerLock['content-hash'] === '') {
                $errors[] = 'composer.lock is missing its Composer content-hash.';
            }
            if (! is_array($composerLock['packages'] ?? null)) {
                $errors[] = 'composer.lock package metadata is missing.';
            }
        }

        $packageLock = $this->decodeJsonFile(base_path('package-lock.json'));
        if ($packageLock === null) {
            $errors[] = 'package-lock.json is missing or invalid.';
        } else {
            if ((int) ($packageLock['lockfileVersion'] ?? 0) < 3) {
                $errors[] = 'package-lock.json must use lockfileVersion 3 or newer.';
            }
            if (! is_array($packageLock['packages'][''] ?? null)) {
                $errors[] = 'package-lock.json root package metadata is missing.';
            }
        }

        if ($lockedLaravelVersion === null) {
            $errors[] = 'composer.lock does not contain a readable laravel/framework version.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array{state:string,data:?array<string,mixed>,error:?string} $review
     * @param array<string, string|null> $hashes
     * @return list<string>
     */
    private function reviewErrors(
        array $review,
        array $hashes,
        ?string $lockedLaravelVersion,
    ): array {
        if ($review['state'] === 'missing') {
            return ['Reviewed dependency-lock attestation is missing.'];
        }

        if ($review['state'] === 'unreadable') {
            return ['Reviewed dependency-lock attestation exists but is unreadable or invalid.'];
        }

        $data = $review['data'];
        if (! is_array($data)) {
            return ['Reviewed dependency-lock attestation is invalid.'];
        }

        $errors = [];
        if (($data['status'] ?? null) !== 'reviewed') {
            $errors[] = 'Reviewed dependency-lock attestation is not in reviewed state.';
        }

        foreach ($hashes as $key => $hash) {
            if (($data[$key] ?? null) !== $hash) {
                $errors[] = "Reviewed dependency fingerprint mismatch [{$key}].";
            }
        }

        if (($data['laravel_framework_locked_version'] ?? null) !== $lockedLaravelVersion) {
            $errors[] = 'Reviewed Laravel framework version does not match composer.lock.';
        }

        return array_values(array_unique($errors));
    }

    /** @param array{state:string,data:?array<string,mixed>,error:?string} $review @param list<string> $errors */
    private function reviewStatus(array $review, array $errors): string
    {
        if ($review['state'] === 'missing') {
            return 'missing';
        }

        if ($review['state'] === 'unreadable') {
            return 'unreadable';
        }

        return $errors === [] ? 'reviewed' : 'stale-or-invalid';
    }

    /** @return array{state:string,data:?array<string,mixed>,error:?string} */
    private function readReview(): array
    {
        $path = base_path(self::REVIEW_PATH);
        if (! is_file($path)) {
            return ['state' => 'missing', 'data' => null, 'error' => null];
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable $exception) {
            return [
                'state' => 'unreadable',
                'data' => null,
                'error' => $exception->getMessage(),
            ];
        }

        if (! is_array($decoded)) {
            return ['state' => 'unreadable', 'data' => null, 'error' => 'JSON root is not an object.'];
        }

        return ['state' => 'present', 'data' => $decoded, 'error' => null];
    }

    /** @return array<string,mixed>|null */
    private function decodeJsonFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function hashFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /** @param array<string, mixed> $materials */
    private function fingerprint(array $materials): string
    {
        ksort($materials, SORT_STRING);

        return hash('sha256', json_encode(
            $materials,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
