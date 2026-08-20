<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Models\RuntimeLease;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use Illuminate\Support\Facades\Schema;

final class UpgradeClusterCoordinator
{
    public function __construct(
        private readonly RuntimeLeaseManager $leases,
        private readonly NodeIdentity $identity,
        private readonly NodeManager $nodes,
        private readonly \App\Nexora\Cloud\Services\RuntimeActivityTracker $activities,
        private readonly \App\Nexora\Cloud\Services\RuntimeVersionGuard $versions,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly RuntimeEnvironmentIdentity $environment,
        private readonly RuntimeActivationIdentity $activation,
        private readonly RuntimeEngineIdentity $engine,
        private readonly RuntimeHostClockIdentity $hostClock,
    ) {}

    /** @return array<string,mixed> */
    public function assess(?string $sourceVersion=null,?string $targetVersion=null): array
    {
        $sourceVersion??='';$targetVersion??=(string)config('nexora.version','');$errors=[];$warnings=[];
        if(!Schema::hasTable('nx_runtime_nodes')||!Schema::hasTable('nx_runtime_leases')){
            return $this->seal(['status'=>'pass','mode'=>'single-runtime-fallback','owner_node_key'=>$this->identity->key(),'fresh_nodes'=>[],'peer_count'=>0,'errors'=>[],'warnings'=>['Runtime node/lease tables unavailable; distributed exclusion will be provided only by the local filesystem lock.']]);
        }
        $this->nodes->heartbeat((string)config('nexora_cloud.node_role','application'));
        $fresh=max(30,(int)config('nexora-upgrade.cluster_fresh_node_seconds',180));
        $dbNow=$this->hostClock->databaseNow();$rows=RuntimeNode::query()->where('last_heartbeat_at','>=',$dbNow->copy()->subSeconds($fresh))->orderBy('node_key')->get();
        $owner=$this->identity->key();$peers=[];$activePeers=[];$invalidVersions=[];$invalidGenerations=[];$invalidEnvironments=[];$invalidActivations=[];$invalidEngines=[];$versionState=$this->versions->assess();$targetGeneration=$this->deployment->generation();$sourceGeneration=$versionState['installed_generation']??null;$targetEnvironment=$this->environment->fingerprintValue();$sourceEnvironment=$versionState['installed_environment_fingerprint']??null;$targetActivation=$this->activation->current();$sourceActivation=$versionState['installed_activation_fingerprint']??null;$targetEngine=$this->engine->fingerprintValue();$sourceEngine=$versionState['installed_runtime_engine_fingerprint']??null;$targetHost=$this->hostClock->fingerprintValue();$sourceHost=$versionState['installed_runtime_host_fingerprint']??null;$invalidHosts=[];
        foreach($rows as $node){
            $meta=is_array($node->metadata)?$node->metadata:[];$generation=strtolower(trim((string)($meta['deployment_generation']??'')));
            $environmentFingerprint=strtolower(trim((string)($meta['runtime_environment_fingerprint']??'')));$activationFingerprint=strtolower(trim((string)($meta['runtime_activation_fingerprint']??'')));$activationEpoch=strtolower(trim((string)($meta['activation_epoch']??'')));$engineFingerprint=strtolower(trim((string)($meta['runtime_engine_fingerprint']??'')));$hostFingerprint=strtolower(trim((string)($meta['runtime_host_fingerprint']??'')));$row=['node_key'=>(string)$node->node_key,'status'=>(string)$node->status,'version'=>(string)$node->version,'deployment_generation'=>$generation!==''?$generation:null,'runtime_environment_fingerprint'=>$environmentFingerprint!==''?$environmentFingerprint:null,'runtime_activation_fingerprint'=>$activationFingerprint!==''?$activationFingerprint:null,'activation_epoch'=>$activationEpoch!==''?$activationEpoch:null,'runtime_engine_fingerprint'=>$engineFingerprint!==''?$engineFingerprint:null,'runtime_host_fingerprint'=>$hostFingerprint!==''?$hostFingerprint:null,'role'=>(string)$node->role,'queue'=>(bool)($node->capabilities['queue']??false),'scheduler'=>(bool)($node->capabilities['scheduler']??false)];
            if($row['node_key']!==$owner){$peers[]=$row;if($row['status']==='active')$activePeers[]=$row['node_key'];}
            if($row['version']!==''&&!in_array($row['version'],array_values(array_filter([$sourceVersion,$targetVersion])),true))$invalidVersions[]=$row['node_key'].'='.$row['version'];
            if($row['version']===$targetVersion&&($generation===''||!hash_equals($targetGeneration,$generation)))$invalidGenerations[]=$row['node_key'].'=target-generation-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&is_string($sourceGeneration)&&$sourceGeneration!==''&&$generation!==''&&!hash_equals($sourceGeneration,$generation))$invalidGenerations[]=$row['node_key'].'=source-generation-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&$generation==='')$warnings[]='Source-version peer ['.$row['node_key'].'] does not advertise deployment generation yet; it must not reactivate after target cutover until updated.';
            if($row['version']===$targetVersion&&($environmentFingerprint===''||!hash_equals($targetEnvironment,$environmentFingerprint)))$invalidEnvironments[]=$row['node_key'].'=target-environment-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&is_string($sourceEnvironment)&&$sourceEnvironment!==''&&$environmentFingerprint!==''&&!hash_equals($sourceEnvironment,$environmentFingerprint))$invalidEnvironments[]=$row['node_key'].'=source-environment-mismatch';
            if($row['version']===$targetVersion&&($activationFingerprint===''||!hash_equals((string)$targetActivation['activation_fingerprint'],$activationFingerprint)))$invalidActivations[]=$row['node_key'].'=target-activation-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&is_string($sourceActivation)&&$sourceActivation!==''&&$activationFingerprint!==''&&!hash_equals($sourceActivation,$activationFingerprint))$invalidActivations[]=$row['node_key'].'=source-activation-mismatch';
            if($row['version']===$targetVersion&&($engineFingerprint===''||!hash_equals($targetEngine,$engineFingerprint)))$invalidEngines[]=$row['node_key'].'=target-engine-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&is_string($sourceEngine)&&$sourceEngine!==''&&$engineFingerprint!==''&&!hash_equals($sourceEngine,$engineFingerprint))$invalidEngines[]=$row['node_key'].'=source-engine-mismatch';
            if($row['version']===$targetVersion&&($hostFingerprint===''||!hash_equals($targetHost,$hostFingerprint)))$invalidHosts[]=$row['node_key'].'=target-host-mismatch';
            if($sourceVersion!==''&&$row['version']===$sourceVersion&&is_string($sourceHost)&&$sourceHost!==''&&$hostFingerprint!==''&&!hash_equals($sourceHost,$hostFingerprint))$invalidHosts[]=$row['node_key'].'=source-host-mismatch';
        }
        $peerKeys=array_values(array_map(static fn(array $row): string=>(string)$row['node_key'],$peers));
        $peerActivities=$peerKeys!==[]?$this->activities->live($peerKeys,['web','queue','scheduler']):[];
        $queueBacklog=$this->activities->queueBacklog();
        if((bool)config('nexora-upgrade.cluster_require_runtime_quiescence',true)&&$peerActivities!==[])$errors[]='Drained peer nodes still have in-flight web/queue/scheduler activity: '.implode(',',array_values(array_unique(array_map(static fn(array $row):string=>(string)$row['owner_node_key'].':'.(string)$row['kind'],$peerActivities))));
        if((bool)config('nexora-upgrade.cluster_require_empty_queue',true)&&(($queueBacklog['status']??null)!=='pass'||(int)($queueBacklog['total']??0)!==0))$errors[]='Shared queue backlog must be empty before schema mutation; backlog='.(string)($queueBacklog['total']??'unknown').'.';
        if($rows->count()>1&&(bool)config('nexora-upgrade.cluster_require_shared_maintenance',true)){
            $maintenanceDriver=(string)config('app.maintenance.driver','file');$maintenanceStore=(string)config('app.maintenance.store','');
            if($maintenanceDriver!=='cache')$errors[]='Multi-node upgrades require APP_MAINTENANCE_DRIVER=cache so maintenance state is shared across nodes.';
            elseif(!in_array($maintenanceStore,(array)config('nexora-ha.shared_cache_stores',[]),true))$errors[]='Multi-node maintenance cache store ['.$maintenanceStore.'] is not in the approved shared cache stores.';
        }
        $lease=RuntimeLease::query()->where('name',(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade'))->first();
        $leaseMeta=is_array($lease?->metadata)?$lease->metadata:[];
        if(($leaseMeta['recovery_required']??false)===true)$errors[]='Distributed upgrade lease is held for recovery review; explicit operator release is required.';
        if((bool)config('nexora-upgrade.require_cluster_quiescence',true)&&$activePeers!==[])$errors[]='Fresh peer nodes remain active and must be drained or placed in maintenance: '.implode(',',$activePeers);
        if($invalidVersions!==[])$errors[]='Fresh runtime node versions are outside the source/target upgrade pair: '.implode(',',$invalidVersions);
        if($invalidGenerations!==[])$errors[]='Fresh runtime node deployment generations are inconsistent with their source/target version identity: '.implode(',',$invalidGenerations);
        if($invalidEnvironments!==[])$errors[]='Fresh runtime node environment fingerprints are inconsistent with the source/target runtime contract: '.implode(',',$invalidEnvironments);
        if($invalidActivations!==[])$errors[]='Fresh runtime node activation/cache fingerprints are inconsistent with the source/target runtime contract: '.implode(',',$invalidActivations);
        if($invalidEngines!==[])$errors[]='Fresh runtime node PHP engine/extension fingerprints are inconsistent with the source/target runtime contract: '.implode(',',$invalidEngines);
        if($invalidHosts!==[])$errors[]='Fresh runtime node host/platform/timezone/locale fingerprints are inconsistent with the source/target runtime contract: '.implode(',',$invalidHosts);
        if((bool)config('nexora-upgrade.cluster_require_scheduler_owner',true)){
            $scheduler=RuntimeLease::query()->where('name','scheduler-leader')->first();
            if($scheduler!==null&&$scheduler->expires_at!==null&&$scheduler->expires_at->gt($this->hostClock->databaseNow())&&$scheduler->owner_node_key!==null&&$scheduler->owner_node_key!==$owner){
                $errors[]='Scheduler leader lease is owned by peer node ['.$scheduler->owner_node_key.']; move/expire scheduler leadership before migrations.';
            }
        }
        return $this->seal(['status'=>$errors===[]?'pass':'fail','mode'=>count($rows)>1?'distributed':'single-node','owner_node_key'=>$owner,'fresh_nodes'=>array_map(static fn(RuntimeNode $n):array=>['node_key'=>(string)$n->node_key,'status'=>(string)$n->status,'version'=>(string)$n->version,'deployment_generation'=>is_array($n->metadata)?($n->metadata['deployment_generation']??null):null,'runtime_environment_fingerprint'=>is_array($n->metadata)?($n->metadata['runtime_environment_fingerprint']??null):null,'runtime_activation_fingerprint'=>is_array($n->metadata)?($n->metadata['runtime_activation_fingerprint']??null):null,'activation_epoch'=>is_array($n->metadata)?($n->metadata['activation_epoch']??null):null,'runtime_engine_fingerprint'=>is_array($n->metadata)?($n->metadata['runtime_engine_fingerprint']??null):null,'runtime_host_fingerprint'=>is_array($n->metadata)?($n->metadata['runtime_host_fingerprint']??null):null,'role'=>(string)$n->role],$rows->all()),'peer_count'=>count($peers),'peer_activities'=>$peerActivities,'queue_backlog'=>$queueBacklog,'errors'=>$errors,'warnings'=>$warnings]);
    }

    /** @return array<string,mixed> */
    public function convergence(?string $targetVersion=null): array
    {
        $targetVersion??=(string)config('nexora.version','');$targetGeneration=$this->deployment->generation();$targetEnvironment=$this->environment->fingerprintValue();$targetActivation=$this->activation->current();$targetEngine=$this->engine->fingerprintValue();$targetHost=$this->hostClock->fingerprintValue();
        if(!Schema::hasTable('nx_runtime_nodes'))return $this->seal(['status'=>'pass','target_version'=>$targetVersion,'target_generation'=>$targetGeneration,'target_environment_fingerprint'=>$targetEnvironment,'target_activation_fingerprint'=>$targetActivation['activation_fingerprint'],'target_activation_epoch'=>$targetActivation['activation_epoch'],'target_runtime_engine_fingerprint'=>$targetEngine,'target_runtime_host_fingerprint'=>$targetHost,'fresh_nodes'=>[],'not_converged'=>[],'mode'=>'single-runtime-fallback']);
        $fresh=max(30,(int)config('nexora-upgrade.cluster_fresh_node_seconds',180));
        $nodes=RuntimeNode::query()->where('last_heartbeat_at','>=',$this->hostClock->databaseNow()->copy()->subSeconds($fresh))->orderBy('node_key')->get();$bad=[];$rows=[];
        foreach($nodes as $node){$meta=is_array($node->metadata)?$node->metadata:[];$generation=strtolower(trim((string)($meta['deployment_generation']??'')));$environmentFingerprint=strtolower(trim((string)($meta['runtime_environment_fingerprint']??'')));$activationFingerprint=strtolower(trim((string)($meta['runtime_activation_fingerprint']??'')));$activationEpoch=strtolower(trim((string)($meta['activation_epoch']??'')));$engineFingerprint=strtolower(trim((string)($meta['runtime_engine_fingerprint']??'')));$hostFingerprint=strtolower(trim((string)($meta['runtime_host_fingerprint']??'')));$row=['node_key'=>(string)$node->node_key,'status'=>(string)$node->status,'version'=>(string)$node->version,'deployment_generation'=>$generation!==''?$generation:null,'runtime_environment_fingerprint'=>$environmentFingerprint!==''?$environmentFingerprint:null,'runtime_activation_fingerprint'=>$activationFingerprint!==''?$activationFingerprint:null,'activation_epoch'=>$activationEpoch!==''?$activationEpoch:null,'runtime_engine_fingerprint'=>$engineFingerprint!==''?$engineFingerprint:null,'runtime_host_fingerprint'=>$hostFingerprint!==''?$hostFingerprint:null];$rows[]=$row;if($row['version']!==$targetVersion||$row['status']!=='active'||$generation===''||!hash_equals($targetGeneration,$generation)||$environmentFingerprint===''||!hash_equals($targetEnvironment,$environmentFingerprint)||$activationFingerprint===''||!hash_equals((string)$targetActivation['activation_fingerprint'],$activationFingerprint)||$activationEpoch===''||!hash_equals((string)$targetActivation['activation_epoch'],$activationEpoch)||$engineFingerprint===''||!hash_equals($targetEngine,$engineFingerprint)||$hostFingerprint===''||!hash_equals($targetHost,$hostFingerprint))$bad[]=$row;}
        return $this->seal(['status'=>$bad===[]?'pass':'pending','target_version'=>$targetVersion,'target_generation'=>$targetGeneration,'target_environment_fingerprint'=>$targetEnvironment,'target_activation_fingerprint'=>$targetActivation['activation_fingerprint'],'target_activation_epoch'=>$targetActivation['activation_epoch'],'target_runtime_engine_fingerprint'=>$targetEngine,'target_runtime_host_fingerprint'=>$targetHost,'fresh_nodes'=>$rows,'not_converged'=>$bad,'mode'=>$nodes->count()>1?'distributed':'single-node']);
    }

    /** @return array<string,mixed> */
    public function acquire(string $upgradeId,string $sourceVersion,string $targetVersion): array
    {
        $assessment=$this->assess($sourceVersion,$targetVersion);
        if(($assessment['status']??null)!=='pass')throw new \RuntimeException('Cluster upgrade preflight failed: '.implode('; ',(array)($assessment['errors']??[])));
        $owner=$this->identity->key();$name=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');
        $ok=$this->leases->acquireOrRenew($name,$owner,(int)config('nexora-upgrade.cluster_lease_seconds',1800),['upgrade_id'=>$upgradeId,'source_version'=>$sourceVersion,'target_version'=>$targetVersion,'target_deployment_generation'=>$this->deployment->generation(),'target_runtime_environment_fingerprint'=>$this->environment->fingerprintValue(),'target_runtime_engine_fingerprint'=>$this->engine->fingerprintValue(),'target_runtime_host_fingerprint'=>$this->hostClock->fingerprintValue(),'recovery_required'=>false]);
        if(!$ok)throw new \RuntimeException('Another runtime node owns the distributed Nexora upgrade lease.');
        return $this->verifyAndRenew($upgradeId,$sourceVersion,$targetVersion);
    }

    /** @return array<string,mixed> */
    public function verifyAndRenew(string $upgradeId,string $sourceVersion,string $targetVersion): array
    {
        $owner=$this->identity->key();$name=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');
        if(Schema::hasTable('nx_runtime_leases')){
            $lease=RuntimeLease::query()->where('name',$name)->first();$meta=is_array($lease?->metadata)?$lease->metadata:[];
            if($lease===null||$lease->owner_node_key!==$owner||($meta['upgrade_id']??null)!==$upgradeId)throw new \RuntimeException('Distributed upgrade lease ownership changed; keep traffic protected and investigate.');
            if(!$this->leases->acquireOrRenew($name,$owner,(int)config('nexora-upgrade.cluster_lease_seconds',1800),$meta))throw new \RuntimeException('Unable to renew distributed upgrade lease.');
            $lease=RuntimeLease::query()->where('name',$name)->first();$meta=is_array($lease?->metadata)?$lease->metadata:[];
            return $this->seal(['status'=>'pass','lease_name'=>$name,'owner_node_key'=>$owner,'upgrade_id'=>$upgradeId,'expires_at'=>$lease?->expires_at?->toIso8601String(),'metadata'=>$meta]);
        }
        return $this->seal(['status'=>'pass','lease_name'=>$name,'owner_node_key'=>$owner,'upgrade_id'=>$upgradeId,'mode'=>'local-fallback']);
    }

    /** @return array<string,mixed> */
    public function currentQuiescenceStatus(): array
    {
        $node=$this->identity->key();$live=$this->activities->live([$node],['web','queue','scheduler']);$queue=$this->activities->queueBacklog();$admission=$this->activities->admissionStatus();$ok=$live===[]&&(!(bool)config('nexora-upgrade.cluster_require_empty_queue',true)||(($queue['status']??null)==='pass'&&(int)($queue['total']??0)===0));
        return $this->seal(['status'=>$ok?'pass':'pending','node_key'=>$node,'live'=>$live,'queue_backlog'=>$queue,'runtime_admission'=>$admission]);
    }

    /** @return array<string,mixed> */
    public function waitForCurrentQuiescence(): array { return $this->activities->waitForNodeQuiescence($this->identity->key()); }

    /** @return array<string,mixed> */
    public function activationAssessment(): array
    {
        $errors=[];$version=$this->versions->assess();if(!$version['compatible'])$errors[]='Local code version does not match installed platform version.';if(app()->isDownForMaintenance())$errors[]='Application maintenance mode is still active.';
        $lease=$this->leaseStatus();if(is_array($lease)){$meta=is_array($lease['metadata']??null)?$lease['metadata']:[];$expires=strtotime((string)($lease['expires_at']??''));if(($meta['recovery_required']??false)===true||($expires!==false&&$expires>$this->hostClock->databaseNow()->getTimestamp()&&($lease['owner_node_key']??null)!==null))$errors[]='Distributed platform-upgrade lease is still active/recovery-held.';}
        $live=$this->activities->live([$this->identity->key()],['web','queue','scheduler']);$current=['status'=>$live===[]?'pass':'pending','node_key'=>$this->identity->key(),'live'=>$live];if($live!==[])$errors[]='Current node still has in-flight runtime activity.';
        return $this->seal(['status'=>$errors===[]?'pass':'fail','version'=>$version,'lease'=>$lease,'quiescence'=>$current,'errors'=>$errors]);
    }

    public function enterMaintenance(): void { if(Schema::hasTable('nx_runtime_nodes'))$this->nodes->setStatus('maintenance'); }
    public function activateCurrent(): void { $this->versions->assertCompatible();if(Schema::hasTable('nx_runtime_nodes')){$this->nodes->heartbeat();$this->nodes->setStatus('active');} }

    public function holdForRecovery(string $upgradeId,string $reason): void
    {
        if(!Schema::hasTable('nx_runtime_leases'))return;$name=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');$owner=$this->identity->key();
        $lease=RuntimeLease::query()->where('name',$name)->where('owner_node_key',$owner)->first();if($lease===null)return;
        $meta=is_array($lease->metadata)?$lease->metadata:[];$meta['upgrade_id']=$upgradeId;$meta['recovery_required']=true;$meta['recovery_reason']=substr($reason,0,500);$meta['recovery_marked_at']=$this->hostClock->databaseNow()->toIso8601String();
        $lease->forceFill(['metadata'=>$meta,'expires_at'=>$this->hostClock->databaseNow()->copy()->addSeconds((int)config('nexora-upgrade.cluster_recovery_hold_seconds',86400)),'heartbeat_at'=>$this->hostClock->databaseNow()])->save();
    }

    public function release(string $upgradeId): void
    {
        if(Schema::hasTable('nx_runtime_leases')){
            $name=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');$lease=RuntimeLease::query()->where('name',$name)->first();$meta=is_array($lease?->metadata)?$lease->metadata:[];
            if($lease!==null&&$lease->owner_node_key===$this->identity->key()&&($meta['upgrade_id']??null)===$upgradeId)$this->leases->release($name,$this->identity->key());
        }
    }

    /** @return array<string,mixed>|null */
    public function leaseStatus(): ?array
    {
        if(!Schema::hasTable('nx_runtime_leases'))return null;$lease=RuntimeLease::query()->where('name',(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade'))->first();if($lease===null)return null;
        return ['name'=>(string)$lease->name,'owner_node_key'=>$lease->owner_node_key,'expires_at'=>$lease->expires_at?->toIso8601String(),'metadata'=>$lease->metadata];
    }

    public function forceReleaseIfSafe(?string $expectedUpgradeId=null): void
    {
        if(!Schema::hasTable('nx_runtime_leases'))return;$name=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');$lease=RuntimeLease::query()->where('name',$name)->first();if($lease===null)return;$meta=is_array($lease->metadata)?$lease->metadata:[];$expired=$lease->expires_at===null||$lease->expires_at->lte($this->hostClock->databaseNow());$recovery=($meta['recovery_required']??false)===true;
        if(!$expired&&!$recovery)throw new \RuntimeException('Distributed upgrade lease is live and cannot be force-released.');if($recovery){$expected=(string)($meta['upgrade_id']??'');if($expected===''||$expectedUpgradeId===null||!hash_equals($expected,$expectedUpgradeId))throw new \RuntimeException('Recovery-held distributed lease release requires the exact upgrade id.');}
        RuntimeLease::query()->where('name',$name)->update(['owner_node_key'=>null,'token'=>null,'expires_at'=>$this->hostClock->databaseNow(),'metadata'=>['released_by_operator'=>true,'released_at'=>$this->hostClock->databaseNow()->toIso8601String(),'previous_upgrade_id'=>$meta['upgrade_id']??null],'updated_at'=>$this->hostClock->databaseNow()]);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function seal(array $payload): array
    {
        $canonical=$payload;unset($canonical['cluster_sha256']);ksort($canonical);$payload['cluster_sha256']=hash('sha256',json_encode($canonical,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return $payload;
    }
}
