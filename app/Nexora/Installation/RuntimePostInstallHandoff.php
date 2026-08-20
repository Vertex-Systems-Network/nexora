<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use RuntimeException;

final class RuntimePostInstallHandoff
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    public function __construct(
        private readonly InstallationState $installation,
        private readonly SourceActivationIdentity $source,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly RuntimeActivationIdentity $activation,
        private readonly RuntimeEnvironmentIdentity $environment,
        private readonly RuntimeServiceDataPlaneIdentity $services,
        private readonly RuntimeProcessPlane $processes,
        private readonly RuntimeVersionGuard $versions,
        private readonly AtomicFileWriter $files,
    ) {
    }

    /** @return array<string,mixed> */
    public function verifyAndRecord(): array
    {
        $this->finalizeCommittedRuntimeIdentity();
        $proof = $this->verifyCurrent();
        $receipt = [
            ...$proof,
            'checked_at' => now()->toIso8601String(),
        ];
        $receipt['receipt_sha256'] = $this->fingerprint($receipt);

        $path = $this->receiptPath();
        $this->files->write(
            $path,
            json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
            0755,
            0600,
        );

        return $receipt;
    }

    /**
     * Finalize the narrow set of runtime fingerprints that are expected to
     * change exactly once when a fresh installer request becomes an installed
     * runtime. This is deliberately fail-closed: source, deployment generation,
     * engine, database, storage, host, resource, policy, framework and dependency
     * identity must already match the sealed installation before any metadata is
     * updated.
     *
     * @return array<string,mixed>
     */
    public function finalizeCommittedRuntimeIdentity(): array
    {
        $lock = $this->installation->inspect();
        if (($lock['valid'] ?? false) !== true) {
            throw new RuntimeException('Post-install identity finalization requires a valid sealed installation lock.');
        }

        $metadata = $this->installation->metadata() ?? [];
        if (($metadata['post_install_identity_finalized'] ?? false) === true) {
            return ['status' => 'already-finalized', 'updated' => false, 'mismatches' => []];
        }

        $this->deployment->forgetMemoizedIdentity();
        $this->environment->forgetMemoizedIdentity();
        $this->services->forgetMemoizedIdentity();
        $this->processes->forgetMemoizedPolicy();
        $this->activation->adoptCurrentEpochForProcess();

        $source = $this->source->inspect();
        $deployment = $this->deployment->deepVerify();
        if (($source['status'] ?? 'fail') !== 'pass' || ($deployment['ok'] ?? false) !== true) {
            throw new RuntimeException('Post-install identity finalization refused because source/deployment verification is not PASS.');
        }

        $compatibility = $this->versions->assess();
        $mismatches = array_values((array) ($compatibility['mismatches'] ?? []));
        $allowed = ['environment', 'activation', 'service', 'process'];
        $hard = array_values(array_diff($mismatches, $allowed));
        if ($hard !== []) {
            throw new RuntimeException(
                'Post-install identity finalization refused because immutable runtime identity changed ['
                .implode(', ', $hard).'].',
            );
        }

        $environment = $this->environment->current();
        $activation = $this->activation->current();
        $services = $this->services->current(true);
        $processes = $this->processes->policy();
        if (($services['status'] ?? 'fail') !== 'pass') {
            throw new RuntimeException('Post-install identity finalization refused because the current service data plane is not healthy.');
        }
        if (($processes['status'] ?? 'fail') !== 'pass') {
            throw new RuntimeException('Post-install identity finalization refused because the current process policy is not valid.');
        }

        $this->installation->updateMetadata([
            'runtime_environment_fingerprint' => $environment['fingerprint'] ?? null,
            'key_fingerprint' => $environment['active_key_fingerprint'] ?? null,
            'activation_epoch' => $activation['activation_epoch'] ?? null,
            'runtime_activation_fingerprint' => $activation['activation_fingerprint'] ?? null,
            'runtime_activation_cache_sha256' => $activation['framework_cache']['snapshot_sha256'] ?? null,
            'runtime_activated_at' => now()->toIso8601String(),
            'runtime_service_fingerprint' => $services['fingerprint'] ?? null,
            'service_deep_probe_sha256' => $services['deep']['deep_sha256'] ?? null,
            'cache_service_store' => $services['materials']['cache']['store'] ?? null,
            'queue_service_connection' => $services['materials']['queue']['connection'] ?? null,
            'mail_service_default' => $services['materials']['mail']['default'] ?? null,
            'runtime_process_fingerprint' => $processes['fingerprint'] ?? null,
            'process_strict_certification_status' => $processes['status'] ?? 'fail',
            'post_install_identity_finalized' => true,
            'post_install_identity_finalized_at' => now()->toIso8601String(),
            'post_install_identity_reconciled_planes' => $mismatches,
        ]);

        $this->deployment->forgetMemoizedIdentity();
        $this->environment->forgetMemoizedIdentity();
        $this->services->forgetMemoizedIdentity();
        $this->processes->forgetMemoizedPolicy();
        $this->activation->adoptCurrentEpochForProcess();
        $after = $this->versions->assess();
        if (($after['compatible'] ?? false) !== true) {
            throw new RuntimeException(
                'Post-install identity finalization did not converge: '
                .implode(', ', (array) ($after['mismatches'] ?? [])).'.',
            );
        }

        return ['status' => 'finalized', 'updated' => true, 'mismatches' => $mismatches];
    }

    /** @return array<string,mixed> */
    public function verifyCurrent(): array
    {
        $lock = $this->installation->inspect();
        if (($lock['valid'] ?? false) !== true) {
            throw new RuntimeException(
                'Post-install runtime handoff refused because the permanent installation lock is not valid.',
            );
        }

        // Installation starts with an uninstalled source-fallback deployment
        // identity. Once installed.lock exists, force the same process to resolve
        // identity exactly as the next web request will resolve it.
        $this->deployment->forgetMemoizedIdentity();
        $this->activation->adoptCurrentEpochForProcess();

        $source = $this->source->inspect();
        $deployment = $this->deployment->deepVerify();
        $compatibility = $this->versions->assess();
        $activation = $this->activation->publicStatus();
        $errors = [];

        if (($source['status'] ?? 'fail') !== 'pass') {
            $errors[] = 'critical source/runtime convergence is not PASS';
        }
        if (($deployment['ok'] ?? false) !== true) {
            foreach ((array) ($deployment['errors'] ?? []) as $error) {
                $errors[] = 'deployment: '.trim((string) $error);
            }
        }
        if (($compatibility['compatible'] ?? false) !== true) {
            $mismatches = array_values((array) ($compatibility['mismatches'] ?? []));
            $errors[] = 'runtime compatibility mismatch: '
                .($mismatches === [] ? 'unknown' : implode(', ', $mismatches));
        }
        if (($activation['status'] ?? 'fail') !== 'pass') {
            $errors[] = 'runtime activation epoch/fingerprint did not converge for the current process';
        }

        if ($errors !== []) {
            throw new RuntimeException(
                'Nexora installation is committed, but post-install runtime handoff is not ready. '
                .implode('; ', array_values(array_unique($errors))).'. '
                .'Run `php artisan nexora:runtime:post-install-status --assert-ready` after reloading/restarting PHP.',
            );
        }

        $metadata = $this->installation->metadata() ?? [];

        return [
            'schema' => 1,
            'status' => 'pass',
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'source_generation' => Installer::SOURCE_GENERATION,
            'installation_id' => $metadata['installation_id'] ?? null,
            'installation_lock_sha256' => $this->hashFile($this->installation->lockPath()),
            'source_tree_sha256' => $deployment['source_tree_sha256'] ?? null,
            'deployment_generation' => $compatibility['current_generation'] ?? null,
            'activation_epoch' => $compatibility['current_activation_epoch'] ?? null,
            'runtime_activation_fingerprint' => $compatibility['current_activation_fingerprint'] ?? null,
            'compatibility_mode' => $compatibility['mode'] ?? null,
            'source_set_fingerprint' => $source['source_set_fingerprint'] ?? null,
            'runtime_class_fingerprint' => $source['runtime_class_fingerprint'] ?? null,
        ];
    }

    /** @return array<string,mixed> */
    public function inspect(): array
    {
        if (! $this->installation->isInstalled()) {
            return [
                'status' => 'not-installed',
                'ready' => false,
                'runtime_ready' => false,
                'receipt_current' => false,
                'errors' => ['Nexora is not installed.'],
            ];
        }

        try {
            $current = $this->verifyCurrent();
            $receipt = $this->readReceipt();
            $receiptCurrent = $this->receiptMatchesCurrent($receipt, $current);

            return [
                'status' => $receiptCurrent ? 'pass' : 'receipt-refresh-required',
                'ready' => $receiptCurrent,
                'runtime_ready' => true,
                'current' => $current,
                'receipt' => $receipt,
                'receipt_current' => $receiptCurrent,
                'errors' => $receiptCurrent
                    ? []
                    : [
                        'Runtime identity is converged, but the sealed post-install handoff receipt is missing or stale. '
                        .'Run `php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE` to record the current committed handoff.',
                    ],
            ];
        } catch (\Throwable $exception) {
            $metadata = $this->installation->metadata() ?? [];
            $hint = ($metadata['post_install_identity_finalized'] ?? false) === true
                ? null
                : 'Run `php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE` to finalize the one-time installer runtime identity transition.';
            return [
                'status' => 'fail',
                'ready' => false,
                'runtime_ready' => false,
                'receipt' => $this->readReceipt(),
                'receipt_current' => false,
                'errors' => array_values(array_filter([$exception->getMessage(), $hint])),
            ];
        }
    }

    /** @return array<string,mixed> */
    public function reconcileReceipt(): array
    {
        return $this->verifyAndRecord();
    }

    private function receiptPath(): string
    {
        return (string) config(
            'installer.post_install_handoff_receipt_path',
            storage_path('app/nexora/runtime/post-install-handoff.json'),
        );
    }

    /** @return array<string,mixed>|null */
    private function readReceipt(): ?array
    {
        $path = $this->receiptPath();
        if (! is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 128, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $expected = strtolower(trim((string) ($decoded['receipt_sha256'] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $expected) !== 1
            || ! hash_equals($expected, $this->fingerprint($decoded))) {
            return null;
        }

        return $decoded;
    }

    /** @param array<string,mixed>|null $receipt @param array<string,mixed> $current */
    private function receiptMatchesCurrent(?array $receipt, array $current): bool
    {
        if (! is_array($receipt)) {
            return false;
        }

        foreach ([
            'platform_version',
            'source_generation',
            'installation_id',
            'installation_lock_sha256',
            'source_tree_sha256',
            'deployment_generation',
            'activation_epoch',
            'runtime_activation_fingerprint',
            'source_set_fingerprint',
            'runtime_class_fingerprint',
        ] as $key) {
            if (($receipt[$key] ?? null) !== ($current[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function hashFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /** @param array<string,mixed> $payload */
    private function fingerprint(array $payload): string
    {
        $copy = $payload;
        unset($copy['receipt_sha256']);
        ksort($copy, SORT_STRING);

        return hash('sha256', json_encode(
            $copy,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
