<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

final class ClusterLeadership
{
    public function __construct(private RuntimeLeaseManager $leases, private NodeIdentity $identity, private NodeManager $nodes, private RuntimeVersionGuard $versions) {}
    public function isSchedulerLeader(): bool
    {
        $this->nodes->heartbeat((string)config('nexora_cloud.node_role','application'));
        if(!$this->nodes->isReady()||!$this->versions->compatible())return false;
        return $this->leases->acquireOrRenew('scheduler-leader',$this->identity->key(),(int)config('nexora_cloud.scheduler_lease_seconds',90),['version'=>(string)config('nexora.version'),'hostname'=>$this->identity->hostname()]);
    }
}
