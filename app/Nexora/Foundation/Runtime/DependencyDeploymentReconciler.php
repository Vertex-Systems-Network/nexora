<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Installation\InstallationState;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class DependencyDeploymentReconciler
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly FrameworkCompatibility $framework,
        private readonly ReviewedDependencyState $dependencies,
        private readonly RuntimeActivationIdentity $activation,
        private readonly AtomicFileWriter $files,
    ) {}

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $installed = $this->installation->metadata();
        $framework = $this->framework->status();
        $dependencies = $this->dependencies->inspect();
        $drift = $this->deployment->installedDriftAssessment();
        $runtimeMatchesLock = $this->runtimeMatchesReviewedLock($framework, $dependencies);

        return [
            'status' => $this->inspectionStatus(
                $installed,
                $framework,
                $dependencies,
                $drift,
                $runtimeMatchesLock,
                (string) ($installed['dependency_trust_mode'] ?? ''),
            ),
            'framework' => $framework,
            'dependencies' => $dependencies,
            'deployment_drift' => $drift,
            'runtime_matches_reviewed_lock' => $runtimeMatchesLock,
            'maintenance_mode' => app()->isDownForMaintenance(),
            'reconciliation_enabled' => (bool) config(
                'nexora-framework.dependency_reconciliation.enabled',
                true,
            ),
            'installed_dependency_trust_mode' => is_array($installed)
                ? (string) ($installed['dependency_trust_mode'] ?? '')
                : null,
            'next_action' => $this->nextAction(
                $framework,
                $dependencies,
                $drift,
                $runtimeMatchesLock,
                (string) ($installed['dependency_trust_mode'] ?? ''),
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function reconcile(string $operator): array
    {
        $operator = trim($operator);
        $this->assertOperator($operator);
        $this->assertReconciliationEnabled();
        $this->assertMaintenanceMode();

        $installed = $this->installation->metadata();
        if (! is_array($installed)) {
            throw new RuntimeException('Nexora must be installed before dependency reconciliation.');
        }

        $framework = $this->framework->assertCompatible();
        $dependencyState = $this->dependencies->inspect();
        $this->assertReviewedDependencies($dependencyState);
        $this->assertInstalledFrameworkMatchesLock($framework, $dependencyState);

        $drift = $this->deployment->installedDriftAssessment();
        $this->assertDependencyOnlyDrift($drift);

        $previousGeneration = (string) ($installed['deployment_generation'] ?? '');
        $candidate = (array) ($drift['candidate'] ?? []);
        $nextGeneration = (string) ($candidate['generation'] ?? '');

        if ($nextGeneration === '') {
            throw new RuntimeException('Unable to calculate the reconciled deployment generation.');
        }

        if ($previousGeneration !== '' && hash_equals($previousGeneration, $nextGeneration)) {
            return [
                'status' => 'no-change',
                'message' => 'Installed deployment generation already matches the reviewed dependency set.',
                'deployment_generation' => $nextGeneration,
                'framework' => $framework,
            ];
        }

        // Clear generated framework caches before rotating activation state. The
        // source tree is already proven unchanged, and this does not mutate code,
        // dependency locks, application data, or the installation version.
        $this->runArtisanOrFail('optimize:clear');

        $hashes = (array) ($dependencyState['hashes'] ?? []);
        $now = now()->toIso8601String();

        $this->installation->updateMetadata([
            'previous_deployment_generation' => $previousGeneration !== ''
                ? $previousGeneration
                : null,
            'deployment_generation' => $nextGeneration,
            'cache_namespace' => 'g'.substr($nextGeneration, 0, 16),
            'composer_lock_sha256' => $hashes['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $hashes['package_lock_sha256'] ?? null,
            'runtime_dependency_fingerprint' => $dependencyState['fingerprint'] ?? null,
            'laravel_framework_version' => $framework['installed_version'] ?? null,
            'dependency_reconciled_at' => $now,
            'dependency_reconciled_by' => $operator,
        ]);

        $this->deployment->forgetMemoizedIdentity();

        $activation = $this->activation->rotate(
            reason: 'dependency-reconcile',
            operator: $operator,
        );

        $this->installation->updateMetadata([
            'activation_epoch' => $activation['activation_epoch'] ?? null,
            'runtime_activation_fingerprint' => $activation['activation_fingerprint'] ?? null,
            'runtime_activation_cache_sha256' => $activation['framework_cache']['snapshot_sha256'] ?? null,
            'runtime_activated_at' => $now,
        ]);

        $this->runArtisanOrFail('queue:restart');

        $receipt = [
            'schema' => 1,
            'status' => 'reconciled',
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'previous_deployment_generation' => $previousGeneration !== ''
                ? $previousGeneration
                : null,
            'deployment_generation' => $nextGeneration,
            'runtime_dependency_fingerprint' => $dependencyState['fingerprint'] ?? null,
            'composer_lock_sha256' => $hashes['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $hashes['package_lock_sha256'] ?? null,
            'laravel_framework_version' => $framework['installed_version'] ?? null,
            'activation_epoch' => $activation['activation_epoch'] ?? null,
            'operator' => $operator,
            'reconciled_at' => $now,
            'maintenance_mode_remains_enabled' => true,
        ];

        $receipt['receipt_sha256'] = $this->fingerprint($receipt);
        $this->writeReceipt($receipt);

        return $receipt;
    }

    /** @param array<string, mixed>|null $installed @param array<string, mixed> $framework @param array<string, mixed> $dependencies @param array<string, mixed> $drift */
    private function inspectionStatus(
        ?array $installed,
        array $framework,
        array $dependencies,
        array $drift,
        bool $runtimeMatchesLock,
        string $installedTrustMode,
    ): string {
        if (! is_array($installed)) {
            return 'not-installed';
        }

        if (($framework['status'] ?? 'fail') !== 'pass') {
            return 'framework-incompatible';
        }

        if (($dependencies['status'] ?? 'fail') !== 'pass') {
            if (($dependencies['runtime_status'] ?? 'fail') === 'pass'
                && ($dependencies['review_status'] ?? 'missing') === 'missing') {
                return 'review-promotion-required';
            }

            return 'review-required';
        }

        if (! $runtimeMatchesLock) {
            return 'install-reviewed-locks-required';
        }

        if (($drift['generation_changed'] ?? false) !== true
            && $installedTrustMode !== 'reviewed') {
            return 'review-sync-required';
        }

        if (($drift['generation_changed'] ?? false) !== true) {
            return 'converged';
        }

        return ($drift['dependency_only'] ?? false) === true
            ? 'dependency-reconcile-required'
            : 'normal-upgrade-required';
    }

    /** @param array<string, mixed> $framework @param array<string, mixed> $dependencies @param array<string, mixed> $drift */
    private function nextAction(
        array $framework,
        array $dependencies,
        array $drift,
        bool $runtimeMatchesLock,
        string $installedTrustMode,
    ): string {
        if (($framework['status'] ?? 'fail') !== 'pass') {
            return 'Install a Laravel version inside the certified 13.x range before continuing.';
        }

        if (($dependencies['status'] ?? 'fail') !== 'pass') {
            if (($dependencies['runtime_status'] ?? 'fail') === 'pass'
                && ($dependencies['review_status'] ?? 'missing') === 'missing') {
                return 'Dependency runtime identity is valid but formal review is pending. '
                    .'Run dependency-lock-review.php --accept, then nexora:runtime:dependency-review-sync.';
            }

            return 'Refresh and review dependency locks before reconciling the deployment identity.';
        }

        if (! $runtimeMatchesLock) {
            return 'Install the reviewed Composer lock so the running Laravel version matches composer.lock, then rerun dependency status.';
        }

        if (($drift['generation_changed'] ?? false) !== true
            && $installedTrustMode !== 'reviewed') {
            return 'Reviewed locks are valid, but installation provenance is still bootstrap-verified. '
                .'Run nexora:runtime:dependency-review-sync with a real operator identity.';
        }

        if (($drift['dependency_only'] ?? false) === true) {
            return 'Enter maintenance mode, then run nexora:runtime:dependency-reconcile with a real operator identity.';
        }

        if (($drift['generation_changed'] ?? false) === true) {
            return 'The drift is broader than dependency locks. Use the normal Nexora upgrade workflow.';
        }

        return 'Runtime dependency identity is already converged.';
    }


    /** @param array<string, mixed> $framework @param array<string, mixed> $dependencyState */
    private function runtimeMatchesReviewedLock(array $framework, array $dependencyState): bool
    {
        $installed = ltrim((string) ($framework['installed_version'] ?? ''), 'v');
        $locked = ltrim((string) ($dependencyState['laravel_framework_locked_version'] ?? ''), 'v');

        return $installed !== ''
            && $locked !== ''
            && version_compare($installed, $locked, '==');
    }

    /** @param array<string, mixed> $framework @param array<string, mixed> $dependencyState */
    private function assertInstalledFrameworkMatchesLock(
        array $framework,
        array $dependencyState,
    ): void {
        $installed = ltrim((string) ($framework['installed_version'] ?? ''), 'v');
        $locked = ltrim((string) ($dependencyState['laravel_framework_locked_version'] ?? ''), 'v');

        if ($installed !== '' && $locked !== '' && version_compare($installed, $locked, '==')) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Installed Laravel version [%s] does not match reviewed composer.lock [%s]. Run the locked Composer install before reconciliation.',
            $installed !== '' ? $installed : 'unknown',
            $locked !== '' ? $locked : 'missing',
        ));
    }

    private function assertOperator(string $operator): void
    {
        if ($operator === '' || in_array(strtolower($operator), [
            'operator',
            'operator-name',
            'your name',
        ], true)) {
            throw new RuntimeException('A real operator identity is required.');
        }
    }

    private function assertReconciliationEnabled(): void
    {
        if (! (bool) config('nexora-framework.dependency_reconciliation.enabled', true)) {
            throw new RuntimeException('Runtime dependency reconciliation is disabled by policy.');
        }
    }

    private function assertMaintenanceMode(): void
    {
        $required = (bool) config(
            'nexora-framework.dependency_reconciliation.require_maintenance_mode',
            true,
        );

        if ($required && ! app()->isDownForMaintenance()) {
            throw new RuntimeException(
                'Dependency reconciliation requires maintenance mode. Run `php artisan down` first.',
            );
        }
    }

    /** @param array<string, mixed> $dependencyState */
    private function assertReviewedDependencies(array $dependencyState): void
    {
        if (! (bool) config(
            'nexora-framework.dependency_reconciliation.require_reviewed_locks',
            true,
        )) {
            return;
        }

        if (($dependencyState['status'] ?? 'fail') === 'pass') {
            return;
        }

        $errors = (array) ($dependencyState['errors'] ?? []);
        throw new RuntimeException(
            'Dependency reconciliation requires current reviewed locks: '.implode('; ', $errors),
        );
    }

    /** @param array<string, mixed> $drift */
    private function assertDependencyOnlyDrift(array $drift): void
    {
        if (($drift['generation_changed'] ?? false) !== true) {
            return;
        }

        if (($drift['dependency_only'] ?? false) === true) {
            return;
        }

        $errors = (array) ($drift['errors'] ?? []);
        $detail = $errors === [] ? 'unknown non-dependency drift' : implode('; ', $errors);

        throw new RuntimeException(
            'Refusing dependency reconciliation because the runtime drift is broader than lockfiles: '.$detail,
        );
    }

    private function runArtisanOrFail(string $command): void
    {
        $exitCode = Artisan::call($command);

        if ($exitCode !== 0) {
            throw new RuntimeException("Artisan command [{$command}] failed during dependency reconciliation.");
        }
    }

    /** @param array<string, mixed> $receipt */
    private function writeReceipt(array $receipt): void
    {
        $path = (string) config(
            'nexora-framework.dependency_reconciliation.receipt_path',
            storage_path('app/nexora/dependency-intake/runtime-dependency-transition.json'),
        );

        $this->files->write(
            path: $path,
            contents: json_encode(
                $receipt,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
            directoryMode: 0755,
            fileMode: 0600,
        );
    }

    /** @param array<string, mixed> $payload */
    private function fingerprint(array $payload): string
    {
        ksort($payload, SORT_STRING);

        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
