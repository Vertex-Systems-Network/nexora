<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeNode;
use Illuminate\Support\Collection;

final class HaReadinessService
{
    public function __construct(
        private readonly RuntimeLeaseManager $leases,
        private readonly RuntimeNodeRegistry $nodes,
        private readonly RuntimeEnvironmentIdentity $environment,
        private readonly RuntimeServiceDataPlaneIdentity $services,
        private readonly RuntimeProcessPlane $processes,
        private readonly RuntimeDeploymentIdentity $deployment,
    ) {
    }

    /** @return array<string,mixed> */
    public function inspect(bool $strict = false): array
    {
        $checks = [];
        $environment = $this->environment->current();
        $services = $this->services->current(true);
        $processes = $this->processes->policy();
        $deployment = $this->deployment->deepVerify();
        $nodes = $this->nodes->active();
        $lease = $this->leases->inspect('scheduler');

        $this->add(
            $checks,
            'shared-cache',
            ($services['materials']['cache']['shared'] ?? false) === true,
            'cache='.(string) ($services['materials']['cache']['store'] ?? 'unknown'),
        );
        $this->add(
            $checks,
            'shared-queue',
            ($services['materials']['queue']['shared'] ?? false) === true,
            'queue='.(string) ($services['materials']['queue']['connection'] ?? 'unknown'),
        );
        $this->add(
            $checks,
            'shared-session',
            ($services['materials']['session']['shared'] ?? false) === true,
            'session='.(string) ($services['materials']['session']['driver'] ?? 'unknown'),
        );
        $this->add(
            $checks,
            'shared-storage',
            ($services['materials']['storage']['shared'] ?? false) === true,
            'storage='.(string) ($services['materials']['storage']['disk'] ?? 'unknown'),
        );
        $this->add(
            $checks,
            'runtime-services',
            ($services['status'] ?? 'fail') === 'pass',
            'status='.(string) ($services['status'] ?? 'fail'),
        );
        $this->add(
            $checks,
            'runtime-processes',
            ($processes['status'] ?? 'fail') === 'pass',
            'status='.(string) ($processes['status'] ?? 'fail'),
        );
        $this->add(
            $checks,
            'deployment-integrity',
            ($deployment['ok'] ?? false) === true,
            'status='.(($deployment['ok'] ?? false) ? 'pass' : 'fail'),
        );

        $activeNodeCount = $nodes->count();
        $this->add(
            $checks,
            'active-nodes',
            ! $strict || $activeNodeCount >= 2,
            'count='.$activeNodeCount,
        );
        $this->add(
            $checks,
            'scheduler-lease',
            ($lease['active'] ?? false) === true,
            'owner='.(string) ($lease['owner'] ?? 'none'),
        );

        $this->addMetadataAgreement(
            $checks,
            $nodes,
            'deployment_generation',
            strtolower(trim((string) ($deployment['generation'] ?? ''))),
            'deployment-generation-agreement',
        );
        $this->addMetadataAgreement(
            $checks,
            $nodes,
            'runtime_environment_fingerprint',
            strtolower(trim((string) ($environment['fingerprint'] ?? ''))),
            'environment-fingerprint-agreement',
        );
        $this->addMetadataAgreement(
            $checks,
            $nodes,
            'runtime_service_fingerprint',
            strtolower(trim((string) ($services['fingerprint'] ?? ''))),
            'service-fingerprint-agreement',
        );
        $this->addMetadataAgreement(
            $checks,
            $nodes,
            'runtime_process_fingerprint',
            strtolower(trim((string) ($processes['fingerprint'] ?? ''))),
            'process-fingerprint-agreement',
        );

        $leaseOwner = strtolower(trim((string) ($lease['owner'] ?? '')));
        $activeNodeIds = $nodes
            ->map(static fn (RuntimeNode $node): string => strtolower(trim((string) $node->node_id)))
            ->filter()
            ->values();
        $this->add(
            $checks,
            'scheduler-owner-active',
            $leaseOwner !== '' && $activeNodeIds->contains($leaseOwner),
            'owner='.$leaseOwner,
        );

        $failed = array_values(array_filter(
            $checks,
            static fn (array $check): bool => ($check['status'] ?? 'fail') !== 'pass',
        ));

        return [
            'status' => $failed === [] ? 'pass' : 'fail',
            'strict' => $strict,
            'checks' => $checks,
            'failed_checks' => array_map(
                static fn (array $check): string => (string) $check['name'],
                $failed,
            ),
            'active_nodes' => $activeNodeCount,
            'scheduler_lease' => $lease,
        ];
    }

    private function addMetadataAgreement(
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

        return strtolower(trim((string) ($metadata[$key] ?? '')));
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
