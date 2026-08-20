<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\RuntimeLease;
use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class UpgradeSchedulerLeaseCommand extends Command
{
    protected $signature='nexora:upgrade:scheduler-lease {--release : Release this node scheduler lease only while node is draining/maintenance} {--confirm= : Must equal RELEASE}';
    protected $description='Inspect or explicitly relinquish scheduler leadership from this drained runtime node during a distributed upgrade.';
    public function handle(RuntimeLeaseManager $leases,NodeIdentity $identity,NodeManager $nodes): int
    {
        if(!Schema::hasTable('nx_runtime_leases')){$this->line(json_encode(['lease'=>null,'runtime_lease_table'=>false]));return self::SUCCESS;}
        $lease=RuntimeLease::query()->where('name','scheduler-leader')->first();$payload=$lease?['owner_node_key'=>$lease->owner_node_key,'expires_at'=>$lease->expires_at?->toIso8601String(),'metadata'=>$lease->metadata]:null;
        if(!$this->option('release')){$this->line(json_encode(['lease'=>$payload,'current_node_status'=>$nodes->status()],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
        if((string)$this->option('confirm')!=='RELEASE'){$this->error('Release refused. Pass --confirm=RELEASE.');return self::INVALID;}
        if($nodes->status()==='active'){$this->error('Release refused while this runtime node is active. Drain or place it in maintenance first.');return self::FAILURE;}
        if($lease===null||$lease->owner_node_key===null){$this->info('No active scheduler lease is owned.');return self::SUCCESS;}
        if($lease->owner_node_key!==$identity->key()){$this->error('Release refused: scheduler lease belongs to another runtime node.');return self::FAILURE;}
        $leases->release('scheduler-leader',$identity->key());$this->info('Scheduler leadership released by the drained node.');return self::SUCCESS;
    }
}
