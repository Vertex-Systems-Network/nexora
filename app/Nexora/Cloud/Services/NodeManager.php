<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeNode;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

final class NodeManager
{
    public function __construct(
        private NodeIdentity $identity,
        private RuntimeDeploymentIdentity $deployment,
        private RuntimeEnvironmentIdentity $environment,
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

    public function heartbeat(?string $role = null): ?RuntimeNode
    {
        if (! Schema::hasTable('nx_runtime_nodes')) {
            return null;
        }

        $role = $this->normalizedRole($role);
        $activation = $this->activation->current();
        $engine = $this->engine->current();
        $host = $this->hostClock->current(false);
        $resources = $this->resources->current(
            (bool) config('nexora-resource-runtime.require_deep_capacity_for_ha', true),
        );
        $policy = $this->policyPlane->current(true);
        $processPolicy = $this->processPlane->policy();
        $framework = $this->framework->status();
        $dependencies = $this->dependencies->inspect();
        $database = $this->safeDatabaseState();
        $storage = $this->safeStorageState();
        $services = $this->safeServiceState();
        $deployment = $this->deployment->current();

        return RuntimeNode::query()->updateOrCreate(
            ['node_key' => $this->identity->key()],
            [
                'hostname' => $this->identity->hostname(),
                'role' => $role,
                'version' => (string) config('nexora.version', 'unknown'),
                'environment' => app()->environment(),
                'capabilities' => $this->capabilities(),
                'metadata' => $this->metadata(
                    deployment: $deployment,
                    activation: $activation,
                    engine: $engine,
                    database: $database,
                    host: $host,
                    resources: $resources,
                    policy: $policy,
                    processPolicy: $processPolicy,
                    storage: $storage,
                    services: $services,
                    framework: $framework,
                    dependencies: $dependencies,
                ),
                'last_heartbeat_at' => $this->hostClock->databaseNow(),
            ],
        );
    }

    public function status(): string
    {
        if (! Schema::hasTable('nx_runtime_nodes')) {
            return 'active';
        }

        return (string) (
            RuntimeNode::query()
                ->where('node_key', $this->identity->key())
                ->value('status')
            ?: 'active'
        );
    }

    public function setStatus(string $status): ?RuntimeNode
    {
        if (! in_array($status, ['active', 'draining', 'maintenance'], true)) {
            throw new InvalidArgumentException('Unsupported node status.');
        }

        $node = $this->heartbeat();
        if ($node === null) {
            return null;
        }

        $node->forceFill([
            'status' => $status,
            'last_heartbeat_at' => $this->hostClock->databaseNow(),
        ])->save();

        return $node->refresh();
    }

    public function isReady(): bool
    {
        return $this->status() === 'active';
    }

    private function normalizedRole(?string $role): string
    {
        $role ??= trim((string) config('nexora_cloud.node_role', 'application'));

        return $role !== '' ? $role : 'application';
    }

    /** @return array<string,bool> */
    private function capabilities(): array
    {
        return [
            'web' => true,
            'queue' => (string) config('queue.default') !== 'sync',
            'scheduler' => true,
            'object_storage' => (string) config('nexora_cloud.object_storage_disk', '') !== '',
        ];
    }

    /**
     * @param array<string,mixed> $deployment
     * @param array<string,mixed> $activation
     * @param array<string,mixed> $engine
     * @param array<string,mixed> $database
     * @param array<string,mixed> $host
     * @param array<string,mixed> $resources
     * @param array<string,mixed> $policy
     * @param array<string,mixed> $processPolicy
     * @param array<string,mixed> $storage
     * @param array<string,mixed> $services
     * @param array<string,mixed> $framework
     * @param array<string,mixed> $dependencies
     * @return array<string,mixed>
     */
    private function metadata(
        array $deployment,
        array $activation,
        array $engine,
        array $database,
        array $host,
        array $resources,
        array $policy,
        array $processPolicy,
        array $storage,
        array $services,
        array $framework,
        array $dependencies,
    ): array {
        return [
            'php' => PHP_VERSION,
            'queue' => (string) config('queue.default'),
            'cache' => (string) config('cache.default'),
            'deployment_generation' => $deployment['generation'] ?? null,
            'frontend_manifest_sha256' => $deployment['frontend_manifest_sha256'] ?? null,
            'runtime_environment_fingerprint' => $this->environment->fingerprintValue(),
            'app_key_fingerprint' => $this->environment->activeKeyFingerprint(),
            'activation_epoch' => $activation['activation_epoch'] ?? null,
            'runtime_activation_fingerprint' => $activation['activation_fingerprint'] ?? null,
            'process_activation_epoch' => $this->activation->processEpoch(),
            'framework_cache_sha256' => $activation['framework_cache']['snapshot_sha256'] ?? null,
            'runtime_engine_fingerprint' => $engine['fingerprint'] ?? null,
            'php_version' => $engine['materials']['php_version'] ?? PHP_VERSION,
            'extension_profile_sha256' => $engine['materials']['extension_profile_sha256'] ?? null,
            'pdo_drivers_sha256' => $engine['materials']['pdo_drivers_sha256'] ?? null,
            'runtime_database_fingerprint' => $database['fingerprint'] ?? null,
            'database_server_version' => $database['normalized_server_version'] ?? null,
            'database_driver' => $database['driver'] ?? null,
            'runtime_host_fingerprint' => $host['fingerprint'] ?? null,
            'runtime_process_fingerprint' => $processPolicy['fingerprint'] ?? null,
            'runtime_process_policy_status' => $processPolicy['status'] ?? 'fail',
            'runtime_policy_fingerprint' => $policy['fingerprint'] ?? null,
            'runtime_policy_status' => $policy['status'] ?? 'fail',
            'runtime_policy_deep_sha256' => $policy['deep']['deep_sha256'] ?? null,
            'runtime_resource_fingerprint' => $resources['fingerprint'] ?? null,
            'runtime_resource_status' => $resources['status'] ?? 'fail',
            'resource_deep_probe_sha256' => $resources['deep']['deep_sha256'] ?? null,
            'resource_memory_limit_bytes' => $resources['snapshot']['memory_limit_bytes'] ?? null,
            'resource_worker_restart_bytes' => $resources['snapshot']['queue_worker_restart_bytes'] ?? null,
            'resource_memory_headroom_bytes' => $resources['deep']['details']['memory']['headroom_bytes'] ?? null,
            'resource_temp_free_bytes' => $resources['deep']['details']['temp']['free_bytes'] ?? null,
            'resource_storage_free_bytes' => $resources['deep']['details']['storage']['free_bytes'] ?? null,
            'resource_transfer_free_bytes' => $resources['deep']['details']['transfer']['free_bytes'] ?? null,
            'resource_backup_staging_free_bytes' => $resources['deep']['details']['backup_staging']['free_bytes'] ?? null,
            'resource_open_files_soft' => $resources['deep']['details']['open_files']['soft'] ?? null,
            'resource_queue_memory_headroom_bytes' => $resources['deep']['details']['queue_memory']['headroom_bytes'] ?? null,
            'host_os_family' => $host['materials']['os_family'] ?? PHP_OS_FAMILY,
            'host_machine_arch' => $host['materials']['machine_arch'] ?? null,
            'host_timezone' => $host['materials']['runtime_timezone'] ?? null,
            'host_locale' => $host['materials']['intl_default_locale'] ?? null,
            'runtime_storage_fingerprint' => $storage['fingerprint'] ?? null,
            'runtime_service_fingerprint' => $services['fingerprint'] ?? null,
            'cache_service_store' => $services['materials']['cache']['store'] ?? null,
            'queue_service_connection' => $services['materials']['queue']['connection'] ?? null,
            'mail_service_transport' => $services['materials']['mail']['profile']['transport'] ?? null,
            'object_storage_driver' => $storage['roles']['object']['driver'] ?? null,
            'object_storage_disk' => $storage['roles']['object']['disk'] ?? null,
            'media_storage_disk' => $storage['roles']['media']['disk'] ?? null,
            'backup_storage_disk' => $storage['roles']['backup']['disk'] ?? null,
            'laravel_framework_version' => $framework['installed_version'] ?? null,
            'runtime_dependency_fingerprint' => $dependencies['fingerprint'] ?? null,
            'dependency_review_status' => $dependencies['status'] ?? 'fail',
            'dependency_review_state' => $dependencies['review_status'] ?? 'missing',
            'dependency_runtime_status' => $dependencies['runtime_status'] ?? 'fail',
            'laravel_framework_locked_version' => $dependencies['laravel_framework_locked_version'] ?? null,
        ];
    }

    /** @return array<string,mixed> */
    private function safeDatabaseState(): array
    {
        try {
            return $this->database->current(false);
        } catch (Throwable) {
            return [
                'fingerprint' => null,
                'normalized_server_version' => null,
                'driver' => null,
            ];
        }
    }

    /** @return array<string,mixed> */
    private function safeStorageState(): array
    {
        try {
            return $this->storage->current(false);
        } catch (Throwable) {
            return ['fingerprint' => null, 'roles' => []];
        }
    }

    /** @return array<string,mixed> */
    private function safeServiceState(): array
    {
        try {
            return $this->services->current(false);
        } catch (Throwable) {
            return ['fingerprint' => null, 'materials' => []];
        }
    }
}
