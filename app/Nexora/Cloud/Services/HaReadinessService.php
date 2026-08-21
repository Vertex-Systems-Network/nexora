<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeLease;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HaReadinessService
{
    public function __construct(
        private ObjectStorageContract $storage,
        private RuntimeVersionGuard $versions,
        private RuntimeEnvironmentIdentity $environment,
        private RuntimeActivationIdentity $activation,
        private RuntimeEngineIdentity $engine,
        private DatabaseDataPlaneIdentity $database,
        private RuntimeStorageDataPlaneIdentity $storageIdentity,
        private RuntimeServiceDataPlaneIdentity $services,
        private RuntimeHostClockIdentity $hostClock,
        private RuntimeResourceEnvelopeIdentity $resources,
        private RuntimePolicyPlaneIdentity $policyPlane,
        private RuntimeProcessPlane $processPlane,
        private FrameworkCompatibility $framework,
        private ReviewedDependencyState $dependencies,
    ) {}

    /** @return array{ready:bool,checks:array<int,array{name:string,status:string,detail:string}>,node_count:int,version:string} */
    public function assess(): array
    {
        $checks = [];
        $version = (string) config('nexora.version', 'unknown');

        $this->sharedInfrastructureChecks($checks);

        $runtimeVersion = $this->versions->assess();
        $this->add(
            $checks,
            'local_runtime_version',
            (bool) $runtimeVersion['compatible'],
            'current='.(string) $runtimeVersion['current_version']
                .'; installed='.(string) ($runtimeVersion['installed_version'] ?? 'bootstrap'),
        );

        $framework = $this->framework->status();
        $dependencies = $this->dependencies->inspect();
        $localDependencyFingerprint = (string) ($dependencies['fingerprint'] ?? '');
        $this->add(
            $checks,
            'local_framework_compatibility',
            ($framework['status'] ?? null) === 'pass',
            'laravel='.(string) ($framework['installed_version'] ?? 'unknown'),
        );
        $this->add(
            $checks,
            'local_reviewed_dependencies',
            ($dependencies['status'] ?? null) === 'pass',
            'dependency_fingerprint='.substr($localDependencyFingerprint, 0, 16),
        );
        $this->add(
            $checks,
            'local_dependency_runtime_identity',
            ($dependencies['runtime_status'] ?? null) === 'pass',
            'runtime_status='.(string) ($dependencies['runtime_status'] ?? 'fail')
                .'; review='.(string) ($dependencies['review_status'] ?? 'missing'),
        );

        $localDatabase = $this->databaseFingerprint($checks);
        $localService = $this->serviceFingerprint($checks);
        $localHost = $this->hostFingerprint($checks);
        $localResource = $this->resourceFingerprint($checks);
        $localPolicy = $this->policyFingerprint($checks);
        $localProcess = $this->processFingerprint($checks);
        $localStorage = $this->storageFingerprint($checks);

        $nodes = $this->freshActiveNodes();
        $nodeCount = $nodes->count();
        $requiredNodes = max(2, (int) config('nexora-ha.required_nodes', 2));

        if (! Schema::hasTable('nx_runtime_nodes')) {
            $this->addMissingNodeTableChecks($checks);
        } else {
            $this->add(
                $checks,
                'fresh_active_nodes',
                $nodeCount >= $requiredNodes,
                "fresh_active_nodes={$nodeCount}; required={$requiredNodes}",
            );
            $this->nodeConvergenceChecks(
                checks: $checks,
                nodes: $nodes,
                version: $version,
                frameworkVersion: (string) ($framework['installed_version'] ?? ''),
                dependencyFingerprint: $localDependencyFingerprint,
                databaseFingerprint: $localDatabase,
                serviceFingerprint: $localService,
                hostFingerprint: $localHost,
                resourceFingerprint: $localResource,
                policyFingerprint: $localPolicy,
                processFingerprint: $localProcess,
                storageFingerprint: $localStorage,
            );
        }

        $this->schedulerLeaderCheck($checks);

        return [
            'ready' => collect($checks)->every(
                static fn (array $check): bool => $check['status'] === 'pass',
            ),
            'checks' => $checks,
            'node_count' => $nodeCount,
            'version' => $version,
        ];
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function sharedInfrastructureChecks(array &$checks): void
    {
        $cache = (string) config('cache.default', 'file');
        $session = (string) config('session.driver', 'file');
        $queue = (string) config('queue.default', 'sync');
        $storageCapabilities = $this->storage->capabilities();
        $storageDriver = (string) ($storageCapabilities['driver'] ?? 'unknown');

        $this->add(
            $checks,
            'shared_cache',
            in_array($cache, (array) config('nexora-ha.shared_cache_stores', []), true),
            "cache={$cache}",
        );
        $this->add(
            $checks,
            'shared_session',
            in_array($session, (array) config('nexora-ha.shared_session_drivers', []), true),
            "session={$session}",
        );
        $this->add(
            $checks,
            'async_queue',
            in_array($queue, (array) config('nexora-ha.async_queue_connections', []), true),
            "queue={$queue}",
        );
        $this->add(
            $checks,
            'shared_object_storage',
            in_array($storageDriver, (array) config('nexora-ha.shared_storage_drivers', []), true),
            "storage_driver={$storageDriver}",
        );
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function databaseFingerprint(array &$checks): string
    {
        try {
            $fingerprint = $this->database->fingerprintValue();
            $this->add(
                $checks,
                'local_database_data_plane',
                preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1,
                'database_fingerprint='.substr($fingerprint, 0, 16),
            );
            return $fingerprint;
        } catch (Throwable $exception) {
            $this->add(
                $checks,
                'local_database_data_plane',
                false,
                'database identity unavailable: '.$exception->getMessage(),
            );
            return '';
        }
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function serviceFingerprint(array &$checks): string
    {
        try {
            $state = $this->services->current(false);
            $fingerprint = (string) ($state['fingerprint'] ?? '');
            $this->add(
                $checks,
                'local_service_data_plane',
                preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1,
                'service_fingerprint='.substr($fingerprint, 0, 16),
            );
            return $fingerprint;
        } catch (Throwable $exception) {
            $this->add(
                $checks,
                'local_service_data_plane',
                false,
                'service identity unavailable: '.$exception->getMessage(),
            );
            return '';
        }
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function hostFingerprint(array &$checks): string
    {
        $host = $this->hostClock->current(false);
        $deep = $this->hostClock->current(true);
        $fingerprint = (string) ($host['fingerprint'] ?? '');
        $skew = $deep['deep']['details']['clock']['skew_ms'] ?? 'unknown';

        $this->add(
            $checks,
            'local_host_clock_profile',
            ($deep['status'] ?? null) === 'pass',
            'host_fingerprint='.substr($fingerprint, 0, 16).'; clock_skew_ms='.$skew,
        );

        return $fingerprint;
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function resourceFingerprint(array &$checks): string
    {
        $resource = $this->resources->current(
            (bool) config('nexora-resource-runtime.require_deep_capacity_for_ha', true),
        );
        $fingerprint = (string) ($resource['fingerprint'] ?? '');
        $deepStatus = (string) ($resource['deep']['status'] ?? $resource['status'] ?? 'unknown');

        $this->add(
            $checks,
            'local_resource_envelope',
            ($resource['status'] ?? null) === 'pass',
            'resource_fingerprint='.substr($fingerprint, 0, 16).'; deep_status='.$deepStatus,
        );

        return $fingerprint;
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function policyFingerprint(array &$checks): string
    {
        $policy = $this->policyPlane->current(true);
        $fingerprint = (string) ($policy['fingerprint'] ?? '');
        $this->add(
            $checks,
            'local_policy_plane',
            ($policy['status'] ?? null) === 'pass',
            'policy_fingerprint='.substr($fingerprint, 0, 16),
        );

        return $fingerprint;
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function processFingerprint(array &$checks): string
    {
        $process = $this->processPlane->current(true);
        $fingerprint = (string) ($process['fingerprint'] ?? '');
        $this->add(
            $checks,
            'local_process_plane',
            ($process['policy']['status'] ?? null) === 'pass',
            'process_fingerprint='.substr($fingerprint, 0, 16),
        );
        $this->addProcessQuorumChecks($checks, $process);

        return $fingerprint;
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks @param array<string,mixed> $process */
    private function addProcessQuorumChecks(array &$checks, array $process): void
    {
        foreach (['web', 'queue', 'scheduler'] as $role) {
            $required = (int) ($process['required'][$role] ?? 0);
            $live = (int) ($process['live']['counts'][$role] ?? 0);
            $passes = $required === 0 || ($process['checks'][$role.'_liveness'] ?? false) === true;
            $this->add(
                $checks,
                $role.'_process_quorum',
                $passes,
                "live={$live}; required={$required}",
            );
        }
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function storageFingerprint(array &$checks): string
    {
        try {
            $storage = $this->storageIdentity->current(false);
            $fingerprint = (string) ($storage['fingerprint'] ?? '');
            $this->add(
                $checks,
                'local_storage_data_plane',
                preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1,
                'storage_fingerprint='.substr($fingerprint, 0, 16),
            );

            $backupShared = (bool) ($storage['roles']['backup']['shared_candidate'] ?? false);
            $this->add(
                $checks,
                'shared_backup_storage',
                ! (bool) config('nexora-storage-runtime.require_backup_shared_for_ha', true)
                    || $backupShared,
                'backup_disk='.(string) ($storage['roles']['backup']['disk'] ?? 'unknown')
                    .'; driver='.(string) ($storage['roles']['backup']['driver'] ?? 'unknown'),
            );

            return $fingerprint;
        } catch (Throwable $exception) {
            $this->add(
                $checks,
                'local_storage_data_plane',
                false,
                'storage identity unavailable: '.$exception->getMessage(),
            );
            $this->add($checks, 'shared_backup_storage', false, 'storage identity unavailable');
            return '';
        }
    }

    /** @return Collection<int,RuntimeNode> */
    private function freshActiveNodes(): Collection
    {
        if (! Schema::hasTable('nx_runtime_nodes')) {
            return collect();
        }

        $freshSeconds = max(30, (int) config('nexora-ha.fresh_node_seconds', 180));
        $threshold = $this->hostClock->databaseNow()->copy()->subSeconds($freshSeconds);

        return RuntimeNode::query()
            ->where('status', 'active')
            ->where('last_heartbeat_at', '>=', $threshold)
            ->get();
    }

    /**
     * @param array<int,array{name:string,status:string,detail:string}> $checks
     * @param Collection<int,RuntimeNode> $nodes
     */
    private function nodeConvergenceChecks(
        array &$checks,
        Collection $nodes,
        string $version,
        string $frameworkVersion,
        string $dependencyFingerprint,
        string $databaseFingerprint,
        string $serviceFingerprint,
        string $hostFingerprint,
        string $resourceFingerprint,
        string $policyFingerprint,
        string $processFingerprint,
        string $storageFingerprint,
    ): void {
        $nodeCount = $nodes->count();
        $versions = $nodes->pluck('version')->filter()->unique()->values();
        $this->add(
            $checks,
            'node_version_consistency',
            $nodeCount > 0 && $versions->count() === 1 && $versions->first() === $version,
            'versions='.$versions->implode(','),
        );

        $this->fingerprintCheck(
            $checks,
            $nodes,
            'runtime_environment_fingerprint',
            $this->environment->fingerprintValue(),
            'runtime_environment_consistency',
        );

        $activation = $this->activation->current();
        $this->fingerprintCheck(
            $checks,
            $nodes,
            'runtime_activation_fingerprint',
            (string) $activation['activation_fingerprint'],
            'runtime_activation_consistency',
        );
        $this->activationEpochCheck($checks, $nodes, (string) $activation['activation_epoch']);

        $this->fingerprintCheck($checks, $nodes, 'runtime_engine_fingerprint', $this->engine->fingerprintValue(), 'runtime_engine_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_database_fingerprint', $databaseFingerprint, 'runtime_database_data_plane_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_storage_fingerprint', $storageFingerprint, 'runtime_storage_data_plane_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_service_fingerprint', $serviceFingerprint, 'runtime_service_data_plane_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_host_fingerprint', $hostFingerprint, 'runtime_host_profile_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_resource_fingerprint', $resourceFingerprint, 'runtime_resource_policy_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_policy_fingerprint', $policyFingerprint, 'runtime_policy_plane_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_process_fingerprint', $processFingerprint, 'runtime_process_policy_consistency');
        $this->fingerprintCheck($checks, $nodes, 'runtime_dependency_fingerprint', $dependencyFingerprint, 'runtime_dependency_fingerprint_consistency');

        $this->frameworkVersionCheck($checks, $nodes, $frameworkVersion);
        $this->metadataStatusCheck($checks, $nodes, 'dependency_review_status', 'dependency_review_status_pass');
        $this->metadataStatusCheck($checks, $nodes, 'runtime_policy_status', 'runtime_policy_status_pass');
        $this->metadataStatusCheck($checks, $nodes, 'runtime_resource_status', 'runtime_resource_capacity_minimums');
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks @param Collection<int,RuntimeNode> $nodes */
    private function activationEpochCheck(array &$checks, Collection $nodes, string $localEpoch): void
    {
        $epochs = $nodes
            ->map(fn (RuntimeNode $node): string => $this->metadataValue($node, 'activation_epoch'))
            ->filter()
            ->unique()
            ->values();

        $this->add(
            $checks,
            'runtime_activation_epoch_consistency',
            $nodes->count() > 0 && $epochs->count() === 1 && $epochs->first() === $localEpoch,
            'epochs='.$epochs->implode(','),
        );
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks @param Collection<int,RuntimeNode> $nodes */
    private function frameworkVersionCheck(array &$checks, Collection $nodes, string $localVersion): void
    {
        $versions = $nodes
            ->map(fn (RuntimeNode $node): string => $this->metadataValue($node, 'laravel_framework_version'))
            ->filter()
            ->unique()
            ->values();

        $this->add(
            $checks,
            'laravel_framework_version_consistency',
            $nodes->count() > 0
                && $localVersion !== ''
                && $versions->count() === 1
                && version_compare((string) $versions->first(), $localVersion, '=='),
            'laravel_versions='.$versions->implode(','),
        );
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks @param Collection<int,RuntimeNode> $nodes */
    private function metadataStatusCheck(
        array &$checks,
        Collection $nodes,
        string $metadataKey,
        string $checkName,
    ): void {
        $statuses = $nodes
            ->map(fn (RuntimeNode $node): string => strtolower($this->metadataValue($node, $metadataKey)))
            ->filter()
            ->unique()
            ->values();

        $this->add(
            $checks,
            $checkName,
            $nodes->count() > 0 && $statuses->count() === 1 && $statuses->first() === 'pass',
            $metadataKey.'='.$statuses->implode(','),
        );
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function addMissingNodeTableChecks(array &$checks): void
    {
        foreach ([
            'fresh_active_nodes',
            'node_version_consistency',
            'runtime_environment_consistency',
            'runtime_activation_consistency',
            'runtime_activation_epoch_consistency',
            'runtime_engine_consistency',
            'runtime_database_data_plane_consistency',
            'runtime_storage_data_plane_consistency',
            'runtime_service_data_plane_consistency',
            'runtime_host_profile_consistency',
            'runtime_resource_policy_consistency',
            'runtime_resource_capacity_minimums',
            'runtime_policy_plane_consistency',
            'runtime_policy_status_pass',
            'runtime_process_policy_consistency',
            'runtime_dependency_fingerprint_consistency',
            'laravel_framework_version_consistency',
            'dependency_review_status_pass',
        ] as $name) {
            $this->add($checks, $name, false, 'runtime node table unavailable');
        }
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function schedulerLeaderCheck(array &$checks): void
    {
        $leaseOk = false;
        $detail = 'no active scheduler leader lease';

        if (! Schema::hasTable('nx_runtime_leases')) {
            $detail = 'runtime lease table unavailable';
        } elseif (! Schema::hasTable('nx_runtime_nodes')) {
            $detail = 'runtime node table unavailable';
        } else {
            $now = $this->hostClock->databaseNow();
            $lease = RuntimeLease::query()->where('name', 'scheduler-leader')->first();
            if ($lease !== null
                && $lease->owner_node_key !== null
                && $lease->expires_at !== null
                && $lease->expires_at->gt($now)) {
                $freshSeconds = max(30, (int) config('nexora-ha.fresh_node_seconds', 180));
                $ownerIsFresh = RuntimeNode::query()
                    ->where('node_key', $lease->owner_node_key)
                    ->where('status', 'active')
                    ->where('last_heartbeat_at', '>=', $now->copy()->subSeconds($freshSeconds))
                    ->exists();

                $leaseOk = $ownerIsFresh;
                $detail = $ownerIsFresh
                    ? 'active scheduler lease owned by a fresh active node'
                    : 'scheduler lease owner is missing, stale, or inactive';
            }
        }

        $this->add(
            $checks,
            'scheduler_leader',
            $leaseOk,
            $detail,
        );
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks @param Collection<int,RuntimeNode> $nodes */
    private function fingerprintCheck(
        array &$checks,
        Collection $nodes,
        string $metadataKey,
        string $local,
        string $name,
    ): void {
        $values = $nodes
            ->map(fn (RuntimeNode $node): string => $this->metadataValue($node, $metadataKey))
            ->filter()
            ->unique()
            ->values();

        $this->add(
            $checks,
            $name,
            $nodes->count() > 0
                && $local !== ''
                && $values->count() === 1
                && $values->first() === $local,
            $metadataKey.'='.$values->implode(','),
        );
    }

    private function metadataValue(RuntimeNode $node, string $key): string
    {
        $metadata = is_array($node->metadata) ? $node->metadata : [];

        return strtolower(trim((string) ($metadata[$key] ?? ''));
    }

    /** @param array<int,array{name:string,status:string,detail:string}> $checks */
    private function add(array &$checks, string $name, bool $ok, string $detail): void
    {
        $checks[] = [
            'name' => $name,
            'status' => $ok ? 'pass' : 'fail',
            'detail' => $detail,
        ];
    }
}
