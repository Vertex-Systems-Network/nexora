<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Foundation\Runtime\FreshInstallDependencyTrust;
use RuntimeException;

final class RuntimeInstallationReadiness
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    public function __construct(
        private readonly SourceActivationIdentity $source,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly FreshInstallDependencyTrust $dependencies,
        private readonly RuntimeHostClockIdentity $hostClock,
        private readonly RuntimeResourceEnvelopeIdentity $resources,
        private readonly RuntimePolicyPlaneIdentity $policy,
        private readonly RuntimeProcessPlane $processes,
        private readonly RuntimeActivationIdentity $activation,
    ) {
    }

    /** @param array<string,mixed>|null $dependencyTrust @return array<string,mixed> */
    public function inspect(?array $dependencyTrust = null): array
    {
        $source = $this->source->inspect();

        // RuntimeDeploymentIdentity is a singleton in the web container. A fresh
        // installation must never inherit an identity memoized by an earlier
        // bootstrap/UI lookup in the same process. CLI commands naturally start
        // fresh, so explicitly converge the browser path before deep verification.
        $this->deployment->forgetMemoizedIdentity();
        $deployment = $this->deployment->deepVerify();
        $dependencies = $dependencyTrust ?? $this->dependencies->inspect();
        $host = $this->hostClock->installationAttestation();
        $resources = $this->resources->installationAttestation();
        $policy = $this->policy->installationAttestation();
        $processes = $this->processes->installationAttestation();
        $activation = $this->activation->installationAttestation();

        $components = [
            'source' => $this->component(
                (string) ($source['status'] ?? 'fail'),
                (array) ($source['errors'] ?? []),
                [],
            ),
            'deployment' => $this->component(
                ($deployment['ok'] ?? false) === true ? 'pass' : 'fail',
                (array) ($deployment['errors'] ?? []),
                ($deployment['source_fallback_identity_refreshed'] ?? false) === true
                    ? ['Browser deployment identity cache was refreshed and converged to the exact current source tree.']
                    : [],
            ),
            'dependencies' => $this->component(
                (string) ($dependencies['status'] ?? 'fail'),
                (array) ($dependencies['errors'] ?? []),
                ($dependencies['review_required'] ?? false) === true
                    ? ['Formal reviewed-lock attestation remains required before final N1.0 certification.']
                    : [],
            ),
            'host_clock' => $this->component(
                (string) ($host['installation_status'] ?? 'fail'),
                (array) ($host['installation_blocking_reasons'] ?? []),
                (array) ($host['installation_warnings'] ?? []),
            ),
            'resources' => $this->component(
                (string) ($resources['installation_status'] ?? 'fail'),
                (array) ($resources['installation_blocking_reasons'] ?? []),
                (array) ($resources['installation_warnings'] ?? []),
            ),
            'policy' => $this->component(
                (string) ($policy['installation_status'] ?? 'fail'),
                (array) ($policy['installation_blocking_reasons'] ?? []),
                (array) ($policy['installation_warnings'] ?? []),
            ),
            'processes' => $this->component(
                (string) ($processes['installation_status'] ?? 'fail'),
                (array) ($processes['installation_blocking_reasons'] ?? []),
                (array) ($processes['installation_warnings'] ?? []),
            ),
            'activation' => $this->component(
                (string) ($activation['installation_status'] ?? 'fail'),
                (array) ($activation['installation_blocking_reasons'] ?? []),
                (array) ($activation['installation_warnings'] ?? []),
            ),
        ];

        $blocking = [];
        $warnings = [];
        foreach ($components as $name => $component) {
            foreach ((array) ($component['blocking_reasons'] ?? []) as $reason) {
                $blocking[] = strtoupper((string) $name).': '.trim((string) $reason);
            }
            foreach ((array) ($component['warnings'] ?? []) as $warning) {
                $warnings[] = strtoupper((string) $name).': '.trim((string) $warning);
            }
        }

        $passed = count(array_filter(
            $components,
            static fn (array $component): bool => ($component['status'] ?? 'fail') === 'pass',
        ));

        return [
            'schema' => 1,
            'status' => $blocking === [] ? 'pass' : 'fail',
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'installer_protocol' => Installer::PROTOCOL,
            'source_generation' => Installer::SOURCE_GENERATION,
            'components_passed' => $passed,
            'components_total' => count($components),
            'components' => $components,
            'blocking_reasons' => array_values(array_unique(array_filter($blocking))),
            'warnings' => array_values(array_unique(array_filter($warnings))),
            'details' => [
                'source' => $source,
                'deployment' => $deployment,
                'dependencies' => $dependencies,
                'host_clock' => $host,
                'resources' => $resources,
                'policy' => $policy,
                'processes' => $processes,
                'activation' => $activation,
            ],
        ];
    }

    /** @param array<string,mixed>|null $dependencyTrust @return array<string,mixed> */
    public function assertReady(?array $dependencyTrust = null): array
    {
        $state = $this->inspect($dependencyTrust);
        if (($state['status'] ?? 'fail') === 'pass') {
            return $state;
        }

        $reasons = array_values((array) ($state['blocking_reasons'] ?? []));
        throw new RuntimeException(
            'Nexora installation runtime readiness failed before database mutation. '
            .($reasons === [] ? 'Unknown readiness blocker.' : implode(' ', $reasons))
            .' Run `php artisan nexora:runtime:install-readiness --json` for exact diagnostics.',
        );
    }

    /** @param list<mixed> $blocking @param list<mixed> $warnings @return array<string,mixed> */
    private function component(string $status, array $blocking, array $warnings): array
    {
        return [
            'status' => $status === 'pass' ? 'pass' : 'fail',
            'blocking_reasons' => array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $blocking,
            ))),
            'warnings' => array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $warnings,
            ))),
        ];
    }
}
