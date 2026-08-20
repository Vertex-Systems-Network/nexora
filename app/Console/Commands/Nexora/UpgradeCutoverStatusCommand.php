<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use Illuminate\Console\Command;
use Throwable;

final class UpgradeCutoverStatusCommand extends Command
{
    protected $signature = 'nexora:upgrade:cutover-status';

    protected $description = 'Inspect cutover admission, queue fences, and runtime compatibility without mutation.';

    public function handle(
        UpgradeClusterCoordinator $cluster,
        RuntimeVersionGuard $versions,
        RuntimeDeploymentIdentity $deployment,
        DatabaseDataPlaneIdentity $database,
        RuntimeStorageDataPlaneIdentity $storage,
        RuntimeServiceDataPlaneIdentity $services,
        RuntimeResourceEnvelopeIdentity $resources,
        RuntimePolicyPlaneIdentity $policyPlane,
        RuntimeProcessPlane $processPlane,
        FrameworkCompatibility $framework,
        ReviewedDependencyState $dependencies,
    ): int {
        try {
            $quiescence = $cluster->currentQuiescenceStatus();
            $version = $versions->assess();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $payload = [
            'schema' => 1,
            'status' => 'observed',
            'platform_version' => (string) config('nexora.version', ''),
            'runtime_version' => $version,
            'framework' => $framework->status(),
            'reviewed_dependencies' => $dependencies->inspect(),
            'dependency_drift' => $deployment->installedDriftAssessment(),
            'deployment' => $deployment->current(),
            'asset_version' => $deployment->assetVersion(),
            'cache_namespace' => $deployment->cacheNamespace(),
            'session_schema' => (int) config('nexora-runtime.deployment.session_schema', 1),
            'database_data_plane_fingerprint' => $database->fingerprintValue(),
            'storage_data_plane_fingerprint' => $storage->fingerprintValue(),
            'service_data_plane_fingerprint' => $services->fingerprintValue(),
            'runtime_resource_fingerprint' => $resources->fingerprintValue(),
            'runtime_policy_fingerprint' => $policyPlane->fingerprintValue(),
            'runtime_process_fingerprint' => $processPlane->fingerprintValue(),
            'runtime_process_plane' => $processPlane->current(false),
            'runtime_resource_policy_sha256' => $resources->current(false)['resource_policy_sha256'] ?? null,
            'runtime_admission' => $quiescence['runtime_admission'] ?? null,
            'live_activity' => $quiescence['live'] ?? [],
            'queue_backlog' => $quiescence['queue_backlog'] ?? null,
            'queue_payload_policy' => $this->queuePolicy(),
            'automatic_cutover' => false,
        ];

        $this->line(json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        return self::SUCCESS;
    }

    /** @return array<string,mixed> */
    private function queuePolicy(): array
    {
        return [
            'schema' => max(13, (int) config('nexora-upgrade.queue_payload_schema', 13)),
            'metadata_required' => (bool) config('nexora-upgrade.queue_payload_require_metadata', true),
            'exact_version_required' => (bool) config('nexora-upgrade.queue_payload_require_exact_version', true),
            'exact_generation_required' => (bool) config('nexora-upgrade.queue_payload_require_exact_generation', true),
            'exact_database_required' => (bool) config('nexora-database-runtime.require_exact_data_plane', true),
            'exact_storage_required' => (bool) config('nexora-storage-runtime.require_exact_data_plane', true),
            'exact_service_required' => (bool) config('nexora-network-runtime.require_exact_service_data_plane', true),
            'exact_resource_policy_required' => (bool) config('nexora-resource-runtime.require_exact_resource_policy', true),
            'exact_policy_plane_required' => (bool) config('nexora-policy-runtime.require_exact_policy_plane', true),
            'exact_process_policy_required' => (bool) config('nexora-process-runtime.require_exact_process_policy', true),
        ];
    }
}
