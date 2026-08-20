<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeLease;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

final readonly class RuntimeActivityTracker
{
    public function __construct(private RuntimeLeaseManager $leases, private NodeIdentity $identity, private RuntimeHostClockIdentity $clock) {}

    /** @param array<string,mixed> $metadata */
    public function begin(string $kind,?string $activityId=null,array $metadata=[]): ?string
    {
        if(!in_array($kind,['web','queue','scheduler'],true)) throw new \InvalidArgumentException('Unsupported runtime activity kind.');
        if(!Schema::hasTable('nx_runtime_leases')) return null;
        $activityId=trim((string)$activityId);if($activityId==='')$activityId=bin2hex(random_bytes(12));
        $node=$this->identity->key();$name=$this->leaseName($kind,$activityId);
        $ttl=(int)config('nexora-upgrade.activity_ttl_'.$kind.'_seconds',$kind==='web'?600:3900);
        $payload=['kind'=>$kind,'activity_id'=>substr($activityId,0,160),'platform_version'=>(string)config('nexora.version'),'pid'=>getmypid(),'started_at'=>$this->clock->databaseNow()->toIso8601String(),...$this->scalarMetadata($metadata)];
        $barrier=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');
        $guarded=(bool)config('nexora-upgrade.runtime_admission_barrier_required',true);
        $acquired=$guarded?$this->leases->acquireActivityUnlessBarrierActive($name,$node,$ttl,$payload,$barrier):$this->leases->acquireOrRenew($name,$node,$ttl,$payload);
        if(!$acquired) throw new \RuntimeException('Runtime activity admission refused ['.$kind.']; the platform upgrade cutover barrier is active or the activity lease could not be acquired.');
        return $name;
    }

    public function end(?string $name): void
    {
        if($name===null||$name==='')return;
        $this->leases->release($name,$this->identity->key());
    }

    public function endActivity(string $kind,string $activityId): void { $this->end($this->leaseName($kind,$activityId)); }

    public function queueActivityId(object $job): string
    {
        try { $id=method_exists($job,'getJobId')?(string)($job->getJobId()??''):''; } catch (\Throwable) { $id=''; }
        return $id!==''?$id:'object-'.spl_object_id($job);
    }

    public function schedulerActivityId(object $task): string
    {
        try { if(method_exists($task,'mutexName')) { $id=(string)$task->mutexName(); if($id!=='')return $id; } } catch (\Throwable) {}
        return 'task-'.spl_object_id($task);
    }

    /** @param list<string>|null $nodeKeys @param list<string>|null $kinds @return list<array<string,mixed>> */
    public function live(?array $nodeKeys=null,?array $kinds=null): array
    {
        if(!Schema::hasTable('nx_runtime_leases'))return [];
        $rows=RuntimeLease::query()->where('name','like','runtime-activity:%')->whereNotNull('owner_node_key')->whereNotNull('expires_at')->where('expires_at','>',$this->clock->databaseNow())->orderBy('name')->get();$out=[];
        foreach($rows as $row){$meta=is_array($row->metadata)?$row->metadata:[];$kind=(string)($meta['kind']??'');$owner=(string)($row->owner_node_key??'');if($nodeKeys!==null&&!in_array($owner,$nodeKeys,true))continue;if($kinds!==null&&!in_array($kind,$kinds,true))continue;$out[]=['name'=>(string)$row->name,'owner_node_key'=>$owner,'kind'=>$kind,'activity_id'=>$meta['activity_id']??null,'platform_version'=>$meta['platform_version']??null,'expires_at'=>$row->expires_at?->toIso8601String()];}
        return $out;
    }

    /** @return array{connection:string,queues:array<string,int>,total:int,status:string} */
    public function queueBacklog(): array
    {
        $connection=(string)config('queue.default','sync');$queues=(array)config('nexora-upgrade.cluster_queue_names',['default']);$counts=[];$total=0;
        if($connection==='sync')return ['connection'=>$connection,'queues'=>['default'=>0],'total'=>0,'status'=>'pass'];
        foreach($queues as $queue){$queue=trim((string)$queue);if($queue==='')continue;try{$size=(int)Queue::connection($connection)->size($queue);$counts[$queue]=$size;$total+=$size;}catch(\Throwable $e){return ['connection'=>$connection,'queues'=>$counts,'total'=>$total,'status'=>'unknown','error'=>substr($e->getMessage(),0,300)];}}
        return ['connection'=>$connection,'queues'=>$counts,'total'=>$total,'status'=>$total===0?'pass':'pending'];
    }

    /** @return array<string,mixed> */
    public function admissionStatus(): array
    {
        $barrier=(string)config('nexora-upgrade.cluster_lease_name','platform-upgrade');$state=$this->leases->barrierStatus($barrier);
        return ['barrier_name'=>$barrier,'barrier_active'=>$state['active'],'owner_node_key'=>$state['owner_node_key'],'expires_at'=>$state['expires_at'],'recovery_required'=>$state['recovery_required'],'new_activity_allowed'=>!$state['active']];
    }

    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $live=$this->live();$by=['web'=>0,'queue'=>0,'scheduler'=>0];foreach($live as $row){$k=(string)($row['kind']??'');if(isset($by[$k]))$by[$k]++;}
        $queue=$this->queueBacklog();$payload=['live'=>$live,'counts'=>$by,'queue_backlog'=>$queue];$payload['activity_sha256']=$this->hash($payload);return $payload;
    }

    /** @return array<string,mixed> */
    public function waitForNodeQuiescence(string $nodeKey): array
    {
        $timeout=max(5,(int)config('nexora-upgrade.cluster_quiescence_wait_seconds',60));$poll=max(100,(int)config('nexora-upgrade.cluster_quiescence_poll_milliseconds',250));$deadline=microtime(true)+$timeout;
        do{$live=$this->live([$nodeKey],['web','queue','scheduler']);$queue=$this->queueBacklog();if($live===[]&&(!(bool)config('nexora-upgrade.cluster_require_empty_queue',true)||(($queue['status']??null)==='pass'&&($queue['total']??0)===0))){$payload=['status'=>'pass','node_key'=>$nodeKey,'live'=>[],'queue_backlog'=>$queue,'waited_ms'=>(int)(($timeout-(max(0,$deadline-microtime(true))))*1000)];$payload['quiescence_sha256']=$this->hash($payload);return $payload;}usleep($poll*1000);}while(microtime(true)<$deadline);
        $payload=['status'=>'fail','node_key'=>$nodeKey,'live'=>$live??[],'queue_backlog'=>$queue??[],'waited_ms'=>$timeout*1000];$payload['quiescence_sha256']=$this->hash($payload);throw new \RuntimeException('Runtime node failed to quiesce before schema mutation. In-flight activities='.count((array)($payload['live']??[])).'; queue backlog='.(string)($payload['queue_backlog']['total']??'unknown').'.');
    }

    private function leaseName(string $kind,string $activityId): string { return 'runtime-activity:'.$kind.':'.substr(hash('sha256',$this->identity->key().'|'.getmypid().'|'.$activityId),0,40); }

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    private function scalarMetadata(array $metadata): array{foreach($metadata as $k=>$v){if(!is_scalar($v)&&$v!==null)unset($metadata[$k]);elseif(is_string($v))$metadata[$k]=substr($v,0,300);}return $metadata;}
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string{$this->sortRecursive($payload);return hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}
    private function sortRecursive(array &$value): void{if(array_is_list($value)){foreach($value as &$v)if(is_array($v))$this->sortRecursive($v);return;}ksort($value);foreach($value as &$v)if(is_array($v))$this->sortRecursive($v);}
}
