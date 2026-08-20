<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use RuntimeException;

final class FreshInstallDependencyTrust
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(
        private readonly ReviewedDependencyState $dependencies,
        private readonly FrameworkCompatibility $framework,
        private readonly AtomicFileWriter $files,
    ) {}

    /** @return array<string,mixed> */
    public function inspect(): array
    {
        $state = $this->dependencies->inspect();
        $framework = $this->framework->status();
        $errors = [];

        if (($framework['status'] ?? 'fail') !== 'pass') {
            $errors[] = 'Running Laravel version is outside the Nexora certified framework range.';
        }

        if (($state['runtime_status'] ?? 'fail') !== 'pass') {
            $errors = array_merge($errors, (array) ($state['identity_errors'] ?? []));
        }

        if ((bool) config('nexora-framework.fresh_install_dependency_trust.require_installed_composer_match', true)) {
            $errors = array_merge($errors, $this->installedComposerRuntimeErrors());
        }

        if ((bool) config('nexora-framework.fresh_install_dependency_trust.require_npm_manifest_lock_match', true)) {
            $errors = array_merge($errors, $this->npmManifestLockErrors());
        }

        $running = ltrim(trim((string) ($framework['installed_version'] ?? '')), 'v');
        $locked = ltrim(trim((string) ($state['laravel_framework_locked_version'] ?? '')), 'v');
        $runtimeMatchesLock = $running !== ''
            && $locked !== ''
            && version_compare($running, $locked, '==');

        if (! $runtimeMatchesLock) {
            $errors[] = sprintf(
                'Running Laravel version [%s] does not match composer.lock [%s].',
                $running !== '' ? $running : 'unknown',
                $locked !== '' ? $locked : 'missing',
            );
        }

        $reviewStatus = (string) ($state['review_status'] ?? 'missing');
        if (! in_array($reviewStatus, ['missing', 'reviewed'], true)) {
            $errors[] = 'Dependency review evidence exists but is unreadable, stale, or invalid.';
        }

        $bootstrapEnabled = (bool) config('nexora-framework.fresh_install_dependency_trust.enabled', true);
        if ($reviewStatus === 'missing' && ! $bootstrapEnabled) {
            $errors[] = 'Fresh-install dependency bootstrap is disabled by policy.';
        }

        $trustMode = $reviewStatus === 'reviewed'
            ? 'reviewed'
            : 'bootstrap-verified';

        return [
            'status' => $errors === [] ? 'pass' : 'fail',
            'trust_mode' => $trustMode,
            'review_required' => $reviewStatus !== 'reviewed',
            'runtime_matches_lock' => $runtimeMatchesLock,
            'dependency_state' => $state,
            'framework' => $framework,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /** @return array<string,mixed> */
    public function resolve(): array
    {
        $inspection = $this->inspect();
        if (($inspection['status'] ?? 'fail') !== 'pass') {
            $errors = implode('; ', (array) ($inspection['errors'] ?? []));
            throw new RuntimeException('Fresh-install dependency trust is not ready: '.$errors);
        }

        $state = (array) ($inspection['dependency_state'] ?? []);
        $framework = (array) ($inspection['framework'] ?? []);

        if (($inspection['trust_mode'] ?? null) === 'reviewed') {
            return [
                ...$inspection,
                'bootstrap_receipt' => null,
            ];
        }

        $receipt = $this->buildBootstrapReceipt($state, $framework);

        return [
            ...$inspection,
            'bootstrap_receipt' => $receipt,
        ];
    }

    /** @return list<string> */
    private function installedComposerRuntimeErrors(): array
    {
        $lock = $this->decodeJson(base_path('composer.lock'));
        $installed = $this->decodeJson(base_path('vendor/composer/installed.json'));

        if ($lock === null) {
            return ['composer.lock is unavailable for installed Composer package verification.'];
        }

        if ($installed === null) {
            return ['vendor/composer/installed.json is unavailable; install composer.lock before running the installer.'];
        }

        $installedRows = is_array($installed['packages'] ?? null)
            ? $installed['packages']
            : $installed;
        $installedVersions = [];

        foreach ($installedRows as $package) {
            if (! is_array($package) || ! is_string($package['name'] ?? null)) {
                continue;
            }

            $installedVersions[(string) $package['name']] = ltrim(
                trim((string) ($package['version'] ?? '')),
                'v',
            );
        }

        $errors = [];
        foreach ((array) ($lock['packages'] ?? []) as $package) {
            if (! is_array($package) || ! is_string($package['name'] ?? null)) {
                continue;
            }

            $name = (string) $package['name'];
            $expected = ltrim(trim((string) ($package['version'] ?? '')), 'v');
            $actual = $installedVersions[$name] ?? null;

            if ($actual === null) {
                $errors[] = "Composer runtime package is missing [{$name}].";
                continue;
            }

            if ($expected !== '' && $actual !== $expected) {
                $errors[] = "Composer runtime package version mismatch [{$name}: {$actual} != {$expected}].";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function npmManifestLockErrors(): array
    {
        $manifest = $this->decodeJson(base_path('package.json'));
        $lock = $this->decodeJson(base_path('package-lock.json'));

        if ($manifest === null || $lock === null) {
            return ['package.json / package-lock.json are unavailable for frontend dependency verification.'];
        }

        $root = $lock['packages'][''] ?? null;
        if (! is_array($root)) {
            return ['package-lock.json root package metadata is unavailable.'];
        }

        $errors = [];
        foreach (['dependencies', 'devDependencies'] as $bucket) {
            $declared = (array) ($manifest[$bucket] ?? []);
            $locked = (array) ($root[$bucket] ?? []);
            ksort($declared, SORT_STRING);
            ksort($locked, SORT_STRING);

            if ($declared !== $locked) {
                $errors[] = "package-lock.json root {$bucket} does not exactly match package.json.";
            }
        }

        return $errors;
    }

    /** @return array<string,mixed>|null */
    private function decodeJson(string $path): ?array
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

    /**
     * Build the receipt in memory first. Publishing it is deliberately separate
     * from validation so a failed final runtime attestation cannot leave a
     * misleading verified receipt behind.
     *
     * @param array<string,mixed> $state
     * @param array<string,mixed> $framework
     * @return array<string,mixed>
     */
    private function buildBootstrapReceipt(array $state, array $framework): array
    {
        $receipt = [
            'schema' => 1,
            'status' => 'verified',
            'trust_mode' => 'fresh-install-bootstrap',
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'runtime_dependency_fingerprint' => $state['fingerprint'] ?? null,
            'composer_manifest_sha256' => $state['hashes']['composer_manifest_sha256'] ?? null,
            'package_manifest_sha256' => $state['hashes']['package_manifest_sha256'] ?? null,
            'composer_lock_sha256' => $state['hashes']['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $state['hashes']['package_lock_sha256'] ?? null,
            'laravel_framework_locked_version' => $state['laravel_framework_locked_version'] ?? null,
            'laravel_framework_running_version' => $framework['installed_version'] ?? null,
            'review_required_for_certification' => true,
            'verified_at' => now()->toIso8601String(),
        ];

        $receipt['receipt_sha256'] = $this->fingerprint($receipt);

        return $receipt;
    }

    /** @param array<string,mixed> $receipt */
    public function commitBootstrapReceipt(array $receipt): void
    {
        $expected = strtolower(trim((string) ($receipt['receipt_sha256'] ?? '')));
        $unsigned = $receipt;
        unset($unsigned['receipt_sha256']);

        if (preg_match('/^[a-f0-9]{64}$/', $expected) !== 1
            || ! hash_equals($expected, $this->fingerprint($unsigned))) {
            throw new RuntimeException('Fresh-install bootstrap receipt failed integrity validation before publication.');
        }

        $payload = json_encode(
            $receipt,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        $this->files->write(
            $this->bootstrapReceiptPath(),
            $payload,
            0755,
            0600,
        );
    }

    /**
     * A bootstrap receipt never grants runtime compatibility by itself. If an
     * earlier installation crashed before installed.lock was committed, remove
     * the orphan before beginning the next installer attempt.
     */
    public function discardOrphanedBootstrapReceipt(): void
    {
        $path = $this->bootstrapReceiptPath();
        if (is_file($path) && ! is_link($path)) {
            @unlink($path);
        }
    }

    private function bootstrapReceiptPath(): string
    {
        return (string) config(
            'nexora-framework.fresh_install_dependency_trust.receipt_path',
            storage_path('app/nexora/dependency-intake/fresh-install-bootstrap.json'),
        );
    }

    /** @param array<string,mixed> $payload */
    private function fingerprint(array $payload): string
    {
        ksort($payload, SORT_STRING);

        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
