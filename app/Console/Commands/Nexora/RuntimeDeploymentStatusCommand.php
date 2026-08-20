<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeKeyRotationService;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use Illuminate\Console\Command;
use Throwable;

final class RuntimeDeploymentStatusCommand extends Command
{
    protected $signature = 'nexora:runtime:deployment-status
        {--deep : Recompute source/material hashes and fail on deployed-file drift}';

    protected $description = 'Inspect deployment identity, dependency compatibility, runtime planes, and cluster convergence.';

    public function handle(
        RuntimeDeploymentIdentity $deployment,
        RuntimeActivationIdentity $activation,
        RuntimeEnvironmentIdentity $environment,
        RuntimeKeyRotationService $rotation,
        RuntimeEngineIdentity $engine,
        RuntimeResourceEnvelopeIdentity $resources,
        RuntimePolicyPlaneIdentity $policyPlane,
        RuntimeProcessPlane $processPlane,
        RuntimeVersionGuard $versions,
        UpgradeClusterCoordinator $cluster,
        FrameworkCompatibility $framework,
        ReviewedDependencyState $dependencies,
    ): int {
        try {
            $identity = $deployment->current();
            $version = $versions->assess();
            $convergence = $cluster->convergence((string) config('nexora.version', ''));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $deep = (bool) $this->option('deep');
        $payload = [
            'schema' => 1,
            'status' => 'observed',
            'deployment' => $identity,
            'runtime_guard' => $version,
            'framework' => $framework->status(),
            'reviewed_dependencies' => $dependencies->inspect(),
            'dependency_drift' => $deployment->installedDriftAssessment(),
            'asset_version' => $deployment->assetVersion(),
            'cache_namespace' => $deployment->cacheNamespace(),
            'runtime_activation' => $activation->publicStatus(),
            'runtime_environment' => $environment->publicStatus(),
            'runtime_engine' => $engine->publicStatus($deep),
            'runtime_resource_envelope' => $resources->current($deep),
            'runtime_policy_plane' => $policyPlane->current($deep),
            'runtime_process_plane' => $processPlane->current(false),
            'key_rotation_state' => $rotation->read() !== null ? 'pending' : 'clear',
            'cache_prefix' => (string) config('cache.prefix', ''),
            'session_schema' => (int) config('nexora-runtime.deployment.session_schema', 1),
            'session_schema_enforced' => (bool) config(
                'nexora-runtime.deployment.session_schema_enforced',
                true,
            ),
            'json_client_generation_fence' => (bool) config(
                'nexora-upgrade.client_generation_require_json_header',
                true,
            ),
            'cluster_convergence' => $convergence,
            'deep_verification' => $deep ? $deployment->deepVerify() : null,
            'mutation_performed' => false,
        ];

        $this->line(json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        if ($deep && ! (bool) ($payload['deep_verification']['ok'] ?? false)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
