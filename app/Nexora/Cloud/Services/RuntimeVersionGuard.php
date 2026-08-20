<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use RuntimeException;
use Throwable;

final readonly class RuntimeVersionGuard
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(
        private InstallationState $installation,
        private RuntimeDeploymentIdentity $deployment,
        private RuntimeEnvironmentIdentity $environment,
        private RuntimeKeyRotationService $keyRotation,
        private RuntimeActivationIdentity $activation,
        private RuntimeEngineIdentity $engine,
        private DatabaseDataPlaneIdentity $database,
        private RuntimeStorageDataPlaneIdentity $storage,
        private RuntimeServiceDataPlaneIdentity $services,
        private RuntimeHostClockIdentity $hostClock,
        private RuntimeResourceEnvelopeIdentity $resources,
        private RuntimePolicyPlaneIdentity $policyPlane,
        private RuntimeProcessPlane $processPlane,
        private FrameworkCompatibility $framework,
        private ReviewedDependencyState $dependencies,
    ) {}

    /** @return array<string, mixed> */
    public function assess(): array
    {
        $current = $this->currentState();
        $installed = $this->installedState();

        if ($installed['version'] === '') {
            return $this->bootstrapAssessment($current);
        }

        $checks = $this->compatibilityChecks($current, $installed);
        $mismatches = array_keys(array_filter(
            $checks,
            static fn (bool $compatible): bool => ! $compatible,
        ));

        return [
            'compatible' => $mismatches === [],
            'mode' => $this->compatibilityMode($current, $installed, $checks),
            'mismatches' => $mismatches,
            'current_version' => $current['version'],
            'installed_version' => $installed['version'],
            'current_generation' => $current['generation'],
            'installed_generation' => $this->nullable($installed['generation']),
            'generation_compatible' => $checks['generation'],
            'current_environment_fingerprint' => $current['environment'],
            'installed_environment_fingerprint' => $this->nullable($installed['environment']),
            'environment_compatible' => $checks['environment'],
            'key_rotation_authorized' => $current['key_rotation_authorized'],
            'current_activation_epoch' => $current['activation_epoch'],
            'process_activation_epoch' => $current['process_activation_epoch'],
            'installed_activation_epoch' => $this->nullable($installed['activation_epoch']),
            'current_activation_fingerprint' => $current['activation'],
            'installed_activation_fingerprint' => $this->nullable($installed['activation']),
            'activation_compatible' => $checks['activation'],
            'process_activation_compatible' => $checks['process_activation'],
            'current_runtime_engine_fingerprint' => $current['engine'],
            'installed_runtime_engine_fingerprint' => $this->nullable($installed['engine']),
            'runtime_engine_compatible' => $checks['engine'],
            'current_database_data_plane_fingerprint' => $this->nullable($current['database']),
            'installed_database_data_plane_fingerprint' => $this->nullable($installed['database']),
            'database_data_plane_compatible' => $checks['database'],
            'current_runtime_storage_fingerprint' => $this->nullable($current['storage']),
            'installed_runtime_storage_fingerprint' => $this->nullable($installed['storage']),
            'runtime_storage_compatible' => $checks['storage'],
            'current_runtime_service_fingerprint' => $this->nullable($current['service']),
            'installed_runtime_service_fingerprint' => $this->nullable($installed['service']),
            'runtime_service_compatible' => $checks['service'],
            'current_runtime_host_fingerprint' => $this->nullable($current['host']),
            'installed_runtime_host_fingerprint' => $this->nullable($installed['host']),
            'runtime_host_compatible' => $checks['host'],
            'current_runtime_resource_fingerprint' => $this->nullable($current['resource']),
            'installed_runtime_resource_fingerprint' => $this->nullable($installed['resource']),
            'runtime_resource_compatible' => $checks['resource'],
            'current_runtime_policy_fingerprint' => $this->nullable($current['policy']),
            'installed_runtime_policy_fingerprint' => $this->nullable($installed['policy']),
            'runtime_policy_compatible' => $checks['policy'],
            'current_runtime_process_fingerprint' => $this->nullable($current['process']),
            'installed_runtime_process_fingerprint' => $this->nullable($installed['process']),
            'runtime_process_compatible' => $checks['process'],
            'current_laravel_framework_version' => $this->nullable($current['framework_version']),
            'installed_laravel_framework_version' => $this->nullable($installed['framework_version']),
            'laravel_framework_compatible' => $checks['framework'],
            'current_runtime_dependency_fingerprint' => $this->nullable($current['dependencies']),
            'current_dependency_runtime_status' => $current['dependencies_status'],
            'current_dependency_review_status' => $current['dependency_review_status'],
            'installed_runtime_dependency_fingerprint' => $this->nullable($installed['dependencies']),
            'runtime_dependencies_compatible' => $checks['dependencies'],
        ];
    }

    public function compatible(): bool
    {
        return (bool) $this->assess()['compatible'];
    }

    /** @param array<string, mixed> $payload @return array{compatible: bool, mode: string, reason: ?string} */
    public function queuePayload(array $payload): array
    {
        $meta = is_array($payload['nexora'] ?? null) ? $payload['nexora'] : null;

        if ($meta === null) {
            if ((bool) config('nexora-upgrade.queue_payload_require_metadata', true)) {
                return $this->queueRejected(
                    'legacy queue payload lacks required Nexora runtime metadata',
                    'legacy',
                );
            }

            return $this->queueAccepted('legacy');
        }

        $expectedSchema = max(13, (int) config('nexora-upgrade.queue_payload_schema', 13));
        if ((int) ($meta['payload_schema'] ?? 0) !== $expectedSchema) {
            return $this->queueRejected('unsupported Nexora queue payload schema');
        }

        $currentVersion = trim((string) config('nexora.version', ''));
        $payloadVersion = trim((string) ($meta['platform_version'] ?? ''));
        if ($payloadVersion === '' || $currentVersion === '') {
            return $this->queueRejected('queue payload platform version missing');
        }

        if (
            (bool) config('nexora-upgrade.queue_payload_require_exact_version', true)
            && ! hash_equals($currentVersion, $payloadVersion)
        ) {
            return $this->queueRejected(
                'queue payload was created by a different Nexora platform version',
            );
        }

        $failure = $this->queueFingerprintFailure($meta);
        if ($failure !== null) {
            return $this->queueRejected($failure);
        }

        $timestamp = $this->hostClock->verifyQueueTimestamp($meta);
        if (! $timestamp['ok']) {
            return $this->queueRejected((string) $timestamp['reason']);
        }

        return $this->queueAccepted();
    }

    /** @return array<string, mixed> */
    public function assertCompatible(): array
    {
        $result = $this->assess();

        if (! $result['compatible']) {
            $mismatches = implode(', ', (array) ($result['mismatches'] ?? []));
            throw new RuntimeException(
                'Runtime identity mismatch ['.$mismatches.']. '
                .'Run `php artisan nexora:runtime:compatibility-status --deep` for exact current / installed values. '
                .'If only reviewed dependency locks changed, use the maintenance-only dependency reconciliation workflow.',
            );
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function currentState(): array
    {
        $deployment = $this->deployment->current();
        $activation = $this->activation->current();
        $engine = $this->engine->current();
        $service = $this->safeServiceState();
        $host = $this->hostClock->current(false);
        $resource = $this->resources->current(false);
        $policy = $this->policyPlane->current(false);
        $process = $this->processPlane->policy();
        $framework = $this->framework->status();
        $dependencies = $this->dependencies->inspect();

        $environment = $this->environment->fingerprintValue();
        $rotationAuthorized = false;

        return [
            'version' => trim((string) config('nexora.version', '')),
            'generation' => (string) $deployment['generation'],
            'environment' => $environment,
            'activation' => (string) $activation['activation_fingerprint'],
            'activation_epoch' => (string) $activation['activation_epoch'],
            'process_activation_epoch' => (string) $activation['process_epoch'],
            'engine' => (string) $engine['fingerprint'],
            'database' => $this->safeFingerprint(fn (): string => $this->database->fingerprintValue()),
            'storage' => $this->safeFingerprint(fn (): string => $this->storage->fingerprintValue()),
            'service' => (string) ($service['fingerprint'] ?? ''),
            'service_status' => (string) ($service['status'] ?? 'fail'),
            'host' => (string) ($host['fingerprint'] ?? ''),
            'host_status' => (string) ($host['status'] ?? 'fail'),
            'resource' => (string) ($resource['fingerprint'] ?? ''),
            'resource_status' => (string) ($resource['status'] ?? 'fail'),
            'policy' => (string) ($policy['fingerprint'] ?? ''),
            'policy_status' => (string) ($policy['status'] ?? 'fail'),
            'process' => (string) ($process['fingerprint'] ?? ''),
            'process_status' => (string) ($process['status'] ?? 'fail'),
            'framework_version' => (string) ($framework['installed_version'] ?? ''),
            'framework_status' => (string) ($framework['status'] ?? 'fail'),
            'dependencies' => (string) ($dependencies['fingerprint'] ?? ''),
            'dependencies_status' => (string) ($dependencies['runtime_status'] ?? 'fail'),
            'dependency_review_status' => (string) ($dependencies['review_status'] ?? 'missing'),
            'key_rotation_authorized' => $rotationAuthorized,
        ];
    }

    /** @return array<string, string> */
    private function installedState(): array
    {
        $metadata = $this->installation->metadata();

        if (! is_array($metadata)) {
            return array_fill_keys([
                'version',
                'generation',
                'environment',
                'activation',
                'activation_epoch',
                'engine',
                'database',
                'storage',
                'service',
                'host',
                'resource',
                'policy',
                'process',
                'framework_version',
                'dependencies',
            ], '');
        }

        return [
            'version' => trim((string) ($metadata['version'] ?? '')),
            'generation' => $this->normalizedHash($metadata['deployment_generation'] ?? null),
            'environment' => $this->normalizedHash($metadata['runtime_environment_fingerprint'] ?? null),
            'activation' => $this->normalizedHash($metadata['runtime_activation_fingerprint'] ?? null),
            'activation_epoch' => $this->normalizedHash($metadata['activation_epoch'] ?? null),
            'engine' => $this->normalizedHash($metadata['runtime_engine_fingerprint'] ?? null),
            'database' => $this->normalizedHash($metadata['database_data_plane_fingerprint'] ?? null),
            'storage' => $this->normalizedHash($metadata['runtime_storage_fingerprint'] ?? null),
            'service' => $this->normalizedHash($metadata['runtime_service_fingerprint'] ?? null),
            'host' => $this->normalizedHash($metadata['runtime_host_fingerprint'] ?? null),
            'resource' => $this->normalizedHash($metadata['runtime_resource_fingerprint'] ?? null),
            'policy' => $this->normalizedHash($metadata['runtime_policy_fingerprint'] ?? null),
            'process' => $this->normalizedHash($metadata['runtime_process_fingerprint'] ?? null),
            'framework_version' => trim((string) ($metadata['laravel_framework_version'] ?? '')),
            'dependencies' => $this->normalizedHash($metadata['runtime_dependency_fingerprint'] ?? null),
        ];
    }

    /** @param array<string, mixed> $current @param array<string, string> $installed @return array<string, bool> */
    private function compatibilityChecks(array &$current, array $installed): array
    {
        $versionCompatible = hash_equals($installed['version'], (string) $current['version']);
        $generationCompatible = $this->generationCompatible(
            installed: $installed['generation'],
            current: (string) $current['generation'],
            versionCompatible: $versionCompatible,
        );

        $environmentCompatible = $this->requiredFingerprintCompatible(
            installed: $installed['environment'],
            current: (string) $current['environment'],
            required: (bool) config('nexora-runtime.deployment.environment_fingerprint_enforced', true),
            legacyAllowed: $versionCompatible && $generationCompatible,
        );

        if (! $environmentCompatible) {
            $rotation = $this->keyRotation->authorizesEnvironmentTransition();
            $current['key_rotation_authorized'] = (bool) ($rotation['authorized'] ?? false);
            $environmentCompatible = $current['key_rotation_authorized'];
        }

        $legacyActivation = $installed['activation'] === '' && $installed['activation_epoch'] === '';
        $activationCompatible = $legacyActivation
            ? ! (bool) config('nexora-activation.require_installed_match', true)
            : hash_equals($installed['activation'], (string) $current['activation'])
                && hash_equals($installed['activation_epoch'], (string) $current['activation_epoch']);

        if ($legacyActivation && $versionCompatible && $generationCompatible && $environmentCompatible) {
            $activationCompatible = true;
        }

        $processActivationCompatible = ! (bool) config(
            'nexora-activation.require_process_epoch_match',
            true,
        ) || hash_equals(
            (string) $current['activation_epoch'],
            (string) $current['process_activation_epoch'],
        );

        if ($legacyActivation) {
            $processActivationCompatible = true;
        }

        return [
            'version' => $versionCompatible,
            'generation' => $generationCompatible,
            'environment' => $environmentCompatible,
            'activation' => $activationCompatible,
            'process_activation' => $processActivationCompatible,
            'engine' => $installed['engine'] === ''
                || hash_equals($installed['engine'], (string) $current['engine']),
            'database' => $this->requiredFingerprintCompatible(
                installed: $installed['database'],
                current: (string) $current['database'],
                required: (bool) config('nexora-database-runtime.require_exact_data_plane', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'storage' => $this->requiredFingerprintCompatible(
                installed: $installed['storage'],
                current: (string) $current['storage'],
                required: (bool) config('nexora-storage-runtime.require_exact_data_plane', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'service' => $this->requiredHealthyFingerprintCompatible(
                installed: $installed['service'],
                current: (string) $current['service'],
                status: (string) $current['service_status'],
                required: (bool) config('nexora-network-runtime.require_exact_service_data_plane', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            // Host/resource/policy/process strict status belongs to C2/C6
            // certification. Runtime admission verifies exact identity here but
            // does not quarantine a correctly installed node solely because a
            // stricter production/HA recommendation is still pending.
            'host' => $this->requiredFingerprintCompatible(
                installed: $installed['host'],
                current: (string) $current['host'],
                required: (bool) config('nexora-host-runtime.require_exact_host_profile', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'resource' => $this->requiredFingerprintCompatible(
                installed: $installed['resource'],
                current: (string) $current['resource'],
                required: (bool) config('nexora-resource-runtime.require_exact_resource_policy', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'policy' => $this->requiredFingerprintCompatible(
                installed: $installed['policy'],
                current: (string) $current['policy'],
                required: (bool) config('nexora-policy-runtime.require_exact_policy_plane', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'process' => $this->requiredFingerprintCompatible(
                installed: $installed['process'],
                current: (string) $current['process'],
                required: (bool) config('nexora-process-runtime.require_exact_process_policy', true),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'framework' => $this->frameworkCompatible(
                installed: $installed['framework_version'],
                current: (string) $current['framework_version'],
                status: (string) $current['framework_status'],
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
            'dependencies' => $this->requiredHealthyFingerprintCompatible(
                installed: $installed['dependencies'],
                current: (string) $current['dependencies'],
                status: (string) $current['dependencies_status'],
                required: (bool) config(
                    'nexora-framework.dependency_reconciliation.require_reviewed_locks',
                    true,
                ),
                legacyAllowed: $versionCompatible && $generationCompatible,
            ),
        ];
    }

    /** @param array<string, mixed> $current @param array<string, string> $installed @param array<string, bool> $checks */
    private function compatibilityMode(array $current, array $installed, array $checks): string
    {
        $legacyOrder = [
            'dependencies' => 'pre-v4.4-dependencies',
            'framework_version' => 'pre-v4.4-framework',
            'process' => 'pre-v4.3-process-plane',
            'policy' => 'pre-v4.2-policy-plane',
            'resource' => 'pre-v4.1-resource-envelope',
            'host' => 'pre-v4.0-host-clock',
            'service' => 'pre-v3.9-services',
            'storage' => 'pre-v3.8-storage',
            'database' => 'pre-v3.7-database',
            'engine' => 'pre-v3.6-engine',
        ];

        foreach ($legacyOrder as $key => $mode) {
            if ($installed[$key] === '') {
                return $mode;
            }
        }

        if ($installed['activation'] === '' && $installed['activation_epoch'] === '') {
            return 'pre-v3.5-activation';
        }

        if ($installed['environment'] === '') {
            return $installed['generation'] === ''
                ? 'installed-version-only'
                : 'installed-generation';
        }

        if (($current['key_rotation_authorized'] ?? false) === true) {
            return 'authorized-key-rotation';
        }

        return $checks['generation'] ? 'installed-data-plane' : 'installed-generation-drift';
    }

    /** @param array<string, mixed> $current @return array<string, mixed> */
    private function bootstrapAssessment(array $current): array
    {
        return [
            'compatible' => true,
            'mode' => 'bootstrap',
            'mismatches' => [],
            'current_version' => $current['version'],
            'installed_version' => null,
            'current_generation' => $current['generation'],
            'installed_generation' => null,
            'generation_compatible' => true,
            'current_environment_fingerprint' => $current['environment'],
            'installed_environment_fingerprint' => null,
            'environment_compatible' => true,
            'key_rotation_authorized' => false,
            'current_activation_epoch' => $current['activation_epoch'],
            'process_activation_epoch' => $current['process_activation_epoch'],
            'installed_activation_epoch' => null,
            'current_activation_fingerprint' => $current['activation'],
            'installed_activation_fingerprint' => null,
            'activation_compatible' => true,
            'process_activation_compatible' => true,
            'current_runtime_engine_fingerprint' => $current['engine'],
            'installed_runtime_engine_fingerprint' => null,
            'runtime_engine_compatible' => true,
            'current_database_data_plane_fingerprint' => $this->nullable($current['database']),
            'installed_database_data_plane_fingerprint' => null,
            'database_data_plane_compatible' => true,
            'current_runtime_storage_fingerprint' => $this->nullable($current['storage']),
            'installed_runtime_storage_fingerprint' => null,
            'runtime_storage_compatible' => true,
            'current_runtime_service_fingerprint' => $this->nullable($current['service']),
            'installed_runtime_service_fingerprint' => null,
            'runtime_service_compatible' => true,
            'current_runtime_host_fingerprint' => $this->nullable($current['host']),
            'installed_runtime_host_fingerprint' => null,
            'runtime_host_compatible' => true,
            'current_runtime_resource_fingerprint' => $this->nullable($current['resource']),
            'installed_runtime_resource_fingerprint' => null,
            'runtime_resource_compatible' => true,
            'current_runtime_policy_fingerprint' => $this->nullable($current['policy']),
            'installed_runtime_policy_fingerprint' => null,
            'runtime_policy_compatible' => true,
            'current_runtime_process_fingerprint' => $this->nullable($current['process']),
            'installed_runtime_process_fingerprint' => null,
            'runtime_process_compatible' => true,
            'current_laravel_framework_version' => $this->nullable($current['framework_version']),
            'installed_laravel_framework_version' => null,
            'laravel_framework_compatible' => true,
            'current_runtime_dependency_fingerprint' => $this->nullable($current['dependencies']),
            'current_dependency_runtime_status' => $current['dependencies_status'],
            'current_dependency_review_status' => $current['dependency_review_status'],
            'installed_runtime_dependency_fingerprint' => null,
            'runtime_dependencies_compatible' => true,
        ];
    }

    /** @param array<string, mixed> $meta */
    private function queueFingerprintFailure(array $meta): ?string
    {
        $checks = [
            [
                'required' => (bool) config('nexora-upgrade.queue_payload_require_exact_generation', true),
                'payload' => $meta['deployment_generation'] ?? null,
                'current' => $this->deployment->generation(),
                'reason' => 'queue payload was created by a different Nexora deployment generation',
            ],
            [
                'required' => (bool) config('nexora-upgrade.queue_payload_require_exact_environment', true),
                'payload' => $meta['runtime_environment_fingerprint'] ?? null,
                'current' => $this->environment->fingerprintValue(),
                'reason' => 'queue payload was created by a different Nexora runtime environment fingerprint',
            ],
            [
                'required' => (bool) config('nexora-engine.require_exact_extension_profile', true),
                'payload' => $meta['runtime_engine_fingerprint'] ?? null,
                'current' => $this->engine->fingerprintValue(),
                'reason' => 'queue payload was created under a different Nexora PHP runtime engine/extension profile',
            ],
            [
                'required' => (bool) config('nexora-database-runtime.require_exact_data_plane', true),
                'payload' => $meta['runtime_database_fingerprint'] ?? null,
                'current' => $this->safeFingerprint(fn (): string => $this->database->fingerprintValue()),
                'reason' => 'queue payload was created against a different Nexora database data-plane fingerprint',
            ],
            [
                'required' => (bool) config('nexora-storage-runtime.require_exact_data_plane', true),
                'payload' => $meta['runtime_storage_fingerprint'] ?? null,
                'current' => $this->safeFingerprint(fn (): string => $this->storage->fingerprintValue()),
                'reason' => 'queue payload was created against a different Nexora persistent storage data-plane fingerprint',
            ],
            [
                'required' => (bool) config('nexora-host-runtime.require_exact_host_profile', true),
                'payload' => $meta['runtime_host_fingerprint'] ?? null,
                'current' => $this->hostClock->fingerprintValue(),
                'reason' => 'queue payload was created under a different Nexora host/platform/timezone/locale profile',
            ],
            [
                'required' => (bool) config('nexora-resource-runtime.require_exact_resource_policy', true),
                'payload' => $meta['runtime_resource_fingerprint'] ?? null,
                'current' => $this->resources->fingerprintValue(),
                'reason' => 'queue payload was created under a different Nexora runtime resource policy envelope',
            ],
            [
                'required' => (bool) config('nexora-policy-runtime.require_exact_policy_plane', true),
                'payload' => $meta['runtime_policy_fingerprint'] ?? null,
                'current' => $this->policyPlane->fingerprintValue(),
                'reason' => 'queue payload was created under a different Nexora effective runtime policy plane',
            ],
            [
                'required' => (bool) config('nexora-process-runtime.require_exact_process_policy', true),
                'payload' => $meta['runtime_process_fingerprint'] ?? null,
                'current' => $this->processPlane->fingerprintValue(),
                'reason' => 'queue payload was created under a different Nexora runtime process-role policy',
            ],
        ];

        foreach ($checks as $check) {
            if (! $check['required']) {
                continue;
            }

            $payloadHash = $this->normalizedHash($check['payload']);
            $currentHash = $this->normalizedHash($check['current']);

            if ($payloadHash === '' || $currentHash === '' || ! hash_equals($currentHash, $payloadHash)) {
                return $check['reason'];
            }
        }

        $activation = $this->activation->current();
        $payloadEpoch = $this->normalizedHash($meta['activation_epoch'] ?? null);
        $payloadActivation = $this->normalizedHash($meta['runtime_activation_fingerprint'] ?? null);

        if (
            (bool) config('nexora-activation.require_exact_queue_activation', true)
            && (
                $payloadEpoch === ''
                || $payloadActivation === ''
                || ! hash_equals((string) $activation['activation_epoch'], $payloadEpoch)
                || ! hash_equals((string) $activation['activation_fingerprint'], $payloadActivation)
            )
        ) {
            return 'queue payload was created under a different Nexora runtime activation epoch/cache fingerprint';
        }

        $serviceState = $this->safeServiceState();
        if (
            (bool) config('nexora-network-runtime.require_exact_service_data_plane', true)
            && (
                ($serviceState['status'] ?? 'fail') !== 'pass'
                || ! $this->hashMatches(
                    $meta['runtime_service_fingerprint'] ?? null,
                    $serviceState['fingerprint'] ?? null,
                )
            )
        ) {
            return 'queue payload was created against a different Nexora cache/session/queue/network service data-plane fingerprint';
        }

        return null;
    }

    private function generationCompatible(
        string $installed,
        string $current,
        bool $versionCompatible,
    ): bool {
        if ($installed === '') {
            return $versionCompatible || ! (bool) config(
                'nexora-upgrade.runtime_generation_require_installed_match',
                true,
            );
        }

        return hash_equals($installed, $current);
    }

    private function frameworkCompatible(
        string $installed,
        string $current,
        string $status,
        bool $legacyAllowed,
    ): bool {
        if ($status !== 'pass') {
            return false;
        }

        if ($installed === '') {
            return $legacyAllowed;
        }

        return $current !== '' && version_compare($installed, $current, '==');
    }

    private function requiredFingerprintCompatible(
        string $installed,
        string $current,
        bool $required,
        bool $legacyAllowed,
    ): bool {
        if ($installed === '') {
            return $legacyAllowed || ! $required;
        }

        return $current !== '' && hash_equals($installed, $current);
    }

    private function requiredHealthyFingerprintCompatible(
        string $installed,
        string $current,
        string $status,
        bool $required,
        bool $legacyAllowed,
    ): bool {
        if ($installed === '') {
            return ($legacyAllowed && $status === 'pass') || ! $required;
        }

        return $status === 'pass'
            && $current !== ''
            && hash_equals($installed, $current);
    }

    /** @return array<string, mixed> */
    private function safeServiceState(): array
    {
        try {
            return $this->services->current(false);
        } catch (Throwable) {
            return ['status' => 'fail', 'fingerprint' => ''];
        }
    }

    private function safeFingerprint(callable $resolver): string
    {
        try {
            return $this->normalizedHash($resolver());
        } catch (Throwable) {
            return '';
        }
    }

    private function hashMatches(mixed $left, mixed $right): bool
    {
        $leftHash = $this->normalizedHash($left);
        $rightHash = $this->normalizedHash($right);

        return $leftHash !== ''
            && $rightHash !== ''
            && hash_equals($leftHash, $rightHash);
    }

    private function normalizedHash(mixed $value): string
    {
        $hash = strtolower(trim((string) $value));

        return preg_match('/^[a-f0-9]{64}$/', $hash) === 1 ? $hash : '';
    }

    private function nullable(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /** @return array{compatible: bool, mode: string, reason: ?string} */
    private function queueRejected(string $reason, string $mode = 'metadata'): array
    {
        return ['compatible' => false, 'mode' => $mode, 'reason' => $reason];
    }

    /** @return array{compatible: bool, mode: string, reason: ?string} */
    private function queueAccepted(string $mode = 'metadata'): array
    {
        return ['compatible' => true, 'mode' => $mode, 'reason' => null];
    }
}
