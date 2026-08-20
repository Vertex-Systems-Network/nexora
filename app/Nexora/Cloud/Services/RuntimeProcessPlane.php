<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeLease;
use Illuminate\Support\Facades\Schema;

final class RuntimeProcessPlane
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private ?array $policyMemo=null;

    public function __construct(
        private RuntimeLeaseManager $leases,
        private RuntimeHostClockIdentity $clock,
        private NodeIdentity $identity,
    ) {}

    /** @return array<string,mixed> */
    public function policy(): array
    {
        if(is_array($this->policyMemo)) return $this->policyMemo;
        $materials=[
            'schema'=>(int)config('nexora-process-runtime.schema',1),
            'lease_seconds'=>(int)config('nexora-process-runtime.lease_seconds',180),
            'heartbeat_throttle_seconds'=>(int)config('nexora-process-runtime.heartbeat_throttle_seconds',30),
            'minimum_web_nodes'=>(int)config('nexora-process-runtime.minimum_web_nodes',2),
            'minimum_queue_nodes'=>(int)config('nexora-process-runtime.minimum_queue_nodes',2),
            'minimum_scheduler_nodes'=>(int)config('nexora-process-runtime.minimum_scheduler_nodes',1),
            'require_web_for_ha'=>(bool)config('nexora-process-runtime.require_web_for_ha',true),
            'require_queue_for_async'=>(bool)config('nexora-process-runtime.require_queue_for_async',true),
            'require_scheduler_for_ha'=>(bool)config('nexora-process-runtime.require_scheduler_for_ha',true),
            'reject_indefinite_queue_blocking_for_ha'=>(bool)config('nexora-process-runtime.reject_indefinite_queue_blocking_for_ha',true),
            'queue_max_block_seconds'=>(int)config('nexora-process-runtime.queue_max_block_seconds',30),
            'queue_connection'=>(string)config('queue.default','sync'),
            'queue_driver'=>(string)config('queue.connections.'.config('queue.default','sync').'.driver',config('queue.default','sync')),
            'queue_block_for'=>$this->queueBlockFor(),
            'queue_payload_schema'=>max(13,(int)config('nexora-process-runtime.queue_payload_schema',13)),
        ];
        $checks=$this->policyChecks($materials);$payload=['schema'=>1,'status'=>in_array(false,$checks,true)?'fail':'pass','fingerprint'=>$this->hash($materials),'materials'=>$materials,'checks'=>$checks];
        return $this->policyMemo=$payload;
    }

    public function fingerprintValue(): string { return (string)$this->policy()['fingerprint']; }
    public function forgetMemoizedPolicy(): void { $this->policyMemo=null; }


    /** @return array<string,mixed> */
    public function installationAttestation(): array
    {
        $strict = $this->policy();
        $materials = (array) ($strict['materials'] ?? []);
        $queue = (string) ($materials['queue_connection'] ?? 'sync');
        $blockFor = $materials['queue_block_for'] ?? null;
        $maxBlock = (int) ($materials['queue_max_block_seconds'] ?? 30);
        $rejectIndefinite = (bool) ($materials['reject_indefinite_queue_blocking_for_ha'] ?? true);
        $blockingSafe = $queue === 'sync'
            || ! $rejectIndefinite
            || $blockFor === null
            || (is_int($blockFor) && $blockFor > 0 && $blockFor <= $maxBlock);

        $checks = [
            'process_policy_enabled' => (bool) config('nexora-process-runtime.require_exact_process_policy', true),
            'lease_exceeds_throttle' => (int) ($materials['lease_seconds'] ?? 0)
                >= (int) ($materials['heartbeat_throttle_seconds'] ?? 0) * 2,
            'queue_blocking_liveness_safe' => $blockingSafe,
            'queue_schema_current' => (int) ($materials['queue_payload_schema'] ?? 0) >= 13,
        ];

        $blocking = [];
        foreach ($checks as $name => $ok) {
            if ($ok !== true) {
                $blocking[] = match ($name) {
                    'process_policy_enabled' => 'The runtime process-role policy is disabled.',
                    'lease_exceeds_throttle' => 'Runtime process lease duration must be at least twice the heartbeat throttle.',
                    'queue_blocking_liveness_safe' => 'Queue blocking configuration can prevent worker liveness/heartbeat renewal.',
                    'queue_schema_current' => 'Runtime process queue payload schema is below Nexora schema 13.',
                    default => "Runtime process installation check failed [{$name}].",
                };
            }
        }

        $warnings = [];
        if (($strict['status'] ?? 'fail') !== 'pass') {
            $failedStrict = array_keys(array_filter(
                (array) ($strict['checks'] ?? []),
                static fn (mixed $ok): bool => $ok !== true,
            ));
            $warnings[] = 'Strict process-role policy certification is not PASS yet. Pending strict checks: '
                .($failedStrict === [] ? 'unknown' : implode(', ', $failedStrict)).'.';
        }

        return [
            ...$strict,
            'installation_status' => $blocking === [] ? 'pass' : 'fail',
            'installation_checks' => $checks,
            'installation_blocking_reasons' => $blocking,
            'installation_warnings' => $warnings,
        ];
    }

    public function heartbeat(string $role): bool
    {
        $role=$this->normalizeRole($role);$policy=$this->policy();
        if(($policy['status']??null)!=='pass') return false;
        $owner=$this->identity->key();$ttl=(int)config('nexora-process-runtime.lease_seconds',180);
        return $this->leases->acquireOrRenew($this->leaseName($role,$owner),$owner,$ttl,[
            'kind'=>'runtime-process','role'=>$role,'platform_version'=>(string)config('nexora.version'),'process_policy_fingerprint'=>$policy['fingerprint'],'pid'=>getmypid(),'sapi'=>PHP_SAPI,
        ]);
    }

    /** @return array<string,mixed> */
    public function current(bool $requireLive=false): array
    {
        $policy=$this->policy();$live=$this->live();$required=$this->requiredCounts();$checks=(array)($policy['checks']??[]);
        if($requireLive){
            foreach(['web','queue','scheduler'] as $role){
                $need=(int)($required[$role]??0);if($need<=0)continue;$checks[$role.'_liveness']=(int)($live['counts'][$role]??0)>=$need;
            }
        }
        return ['schema'=>1,'status'=>in_array(false,$checks,true)?'fail':'pass','fingerprint'=>$policy['fingerprint'],'policy'=>$policy,'required'=>$required,'live'=>$live,'checks'=>$checks];
    }

    /** @return array{rows:list<array<string,mixed>>,counts:array<string,int>,nodes:array<string,list<string>>,observed_at:string} */
    public function live(): array
    {
        $rows=[];$counts=['web'=>0,'queue'=>0,'scheduler'=>0];$nodes=['web'=>[],'queue'=>[],'scheduler'=>[]];$now=$this->clock->databaseNow();
        if(Schema::hasTable('nx_runtime_leases')){
            foreach(RuntimeLease::query()->where('name','like','runtime-process:%')->whereNotNull('owner_node_key')->whereNotNull('expires_at')->where('expires_at','>',$now)->orderBy('name')->get() as $lease){
                $meta=is_array($lease->metadata)?$lease->metadata:[];$role=(string)($meta['role']??'');if(!array_key_exists($role,$counts))continue;$owner=(string)($lease->owner_node_key??'');
                $rows[]=['role'=>$role,'owner_node_key'=>$owner,'expires_at'=>$lease->expires_at?->toIso8601String(),'platform_version'=>$meta['platform_version']??null,'process_policy_fingerprint'=>$meta['process_policy_fingerprint']??null,'sapi'=>$meta['sapi']??null];
                if($owner!==''&&!in_array($owner,$nodes[$role],true))$nodes[$role][]=$owner;
            }
        }
        foreach($nodes as $role=>$owners){sort($owners,SORT_STRING);$nodes[$role]=$owners;$counts[$role]=count($owners);}
        return ['rows'=>$rows,'counts'=>$counts,'nodes'=>$nodes,'observed_at'=>$now->toIso8601String()];
    }

    /** @return array{web:int,queue:int,scheduler:int} */
    public function requiredCounts(): array
    {
        $async=(string)config('queue.default','sync')!=='sync';
        return [
            'web'=>(bool)config('nexora-process-runtime.require_web_for_ha',true)?max(1,(int)config('nexora-process-runtime.minimum_web_nodes',2)):0,
            'queue'=>$async&&(bool)config('nexora-process-runtime.require_queue_for_async',true)?max(1,(int)config('nexora-process-runtime.minimum_queue_nodes',2)):0,
            'scheduler'=>(bool)config('nexora-process-runtime.require_scheduler_for_ha',true)?max(1,(int)config('nexora-process-runtime.minimum_scheduler_nodes',1)):0,
        ];
    }

    /** @return array<string,bool> */
    private function policyChecks(array $materials): array
    {
        $queue=(string)($materials['queue_connection']??'sync');$block=$materials['queue_block_for']??null;$max=(int)($materials['queue_max_block_seconds']??30);$reject=(bool)($materials['reject_indefinite_queue_blocking_for_ha']??true);
        $blockingSafe=$queue==='sync'||!$reject||$block===null||(is_int($block)&&$block>0&&$block<=$max);
        return [
            'process_policy_enabled'=>(bool)config('nexora-process-runtime.require_exact_process_policy',true),
            'lease_exceeds_throttle'=>(int)($materials['lease_seconds']??0)>=(int)($materials['heartbeat_throttle_seconds']??0)*2,
            'role_minimums_valid'=>(int)($materials['minimum_web_nodes']??0)>=1&&(int)($materials['minimum_queue_nodes']??0)>=1&&(int)($materials['minimum_scheduler_nodes']??0)>=1,
            'queue_blocking_liveness_safe'=>$blockingSafe,
            'queue_schema_current'=>(int)($materials['queue_payload_schema']??0)>=13,
        ];
    }

    private function queueBlockFor(): ?int
    {
        $connection=(string)config('queue.default','sync');$value=config("queue.connections.{$connection}.block_for");if($value===null)return null;return is_numeric($value)?(int)$value:null;
    }
    private function normalizeRole(string $role): string { $role=strtolower(trim($role));if(!in_array($role,['web','queue','scheduler'],true))throw new \InvalidArgumentException('Runtime process role must be web, queue or scheduler.');return $role; }
    private function leaseName(string $role,string $node): string { return 'runtime-process:'.$role.':'.substr(hash('sha256',$node),0,40); }
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string { return hash('sha256',json_encode($this->canonicalize($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function canonicalize(mixed $value): mixed { if(!is_array($value))return $value;if(array_is_list($value)){foreach($value as &$v)$v=$this->canonicalize($v);unset($v);return $value;}ksort($value,SORT_STRING);foreach($value as $k=>$v)$value[$k]=$this->canonicalize($v);return $value; }
}
