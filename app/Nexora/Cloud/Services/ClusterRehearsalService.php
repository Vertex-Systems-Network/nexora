<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use Illuminate\Support\Str;

final class ClusterRehearsalService
{
    public function __construct(
        private RuntimeLeaseManager $leases,
        private HealthProbeService $health,
        private RuntimeTopology $topology,
    ) {}

    /**
     * Safe local rehearsal of the HA primitives. This is not proof of a multi-host deployment.
     * @return array<string,mixed>
     */
    public function run(): array
    {
        $lease = 'ha-rehearsal-'.Str::lower(Str::random(12));
        $ownerA = 'rehearsal-a-'.Str::lower(Str::random(8));
        $ownerB = 'rehearsal-b-'.Str::lower(Str::random(8));
        $a = $this->leases->acquireOrRenew($lease, $ownerA, 30, ['rehearsal' => true]);
        $bWhileA = $this->leases->acquireOrRenew($lease, $ownerB, 30, ['rehearsal' => true]);
        $this->leases->release($lease, $ownerA);
        $bAfterRelease = $this->leases->acquireOrRenew($lease, $ownerB, 30, ['rehearsal' => true]);
        $this->leases->release($lease, $ownerB);

        $health = $this->health->readiness(true);
        $topology = $this->topology->snapshot();
        $pass = $a && ! $bWhileA && $bAfterRelease && ($health['ready'] ?? false);

        return [
            'status' => $pass ? 'pass' : 'fail',
            'lease_exclusion' => ['owner_a_acquired' => $a, 'owner_b_blocked' => ! $bWhileA, 'owner_b_failover' => $bAfterRelease],
            'deep_readiness' => $health,
            'topology' => $topology,
            'note' => 'This rehearses application primitives on one runtime. Final HA certification still requires observed evidence from two or more independent nodes.',
        ];
    }
}
