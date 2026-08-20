<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeNode;
use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Installation\InstallationState;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class RuntimeKeyRotationService
{
    public function __construct(private readonly RuntimeEnvironmentIdentity $environment,private readonly InstallationState $installation,private readonly AtomicFileWriter $files,private readonly RuntimeHostClockIdentity $clock) {}

    public function path(): string { return (string)config('nexora-runtime.deployment.key_rotation_receipt_path',base_path('storage/app/nexora/runtime/key-rotation.json')); }
    public function historyPath(): string { return (string)config('nexora-runtime.deployment.key_rotation_history_path',base_path('storage/app/nexora/runtime/key-rotation-history')); }

    /** @return array<string,mixed>|null */
    public function read(): ?array
    {
        if(!is_file($this->path()))return null;
        try{$d=json_decode((string)file_get_contents($this->path()),true,128,JSON_THROW_ON_ERROR);}catch(\Throwable){return null;}
        return is_array($d)?$d:null;
    }

    /** @return list<string> */
    public function validate(?array $receipt=null): array
    {
        $errors=[];$receipt=$receipt??$this->read();if(!is_array($receipt))return ['key rotation receipt missing'];
        if(($receipt['schema']??null)!==1||($receipt['status']??null)!=='authorized')$errors[]='key rotation receipt schema/status invalid';
        $declared=strtolower(trim((string)($receipt['receipt_sha256']??'')));$actual=$this->seal($receipt);if($declared===''||!hash_equals($declared,$actual))$errors[]='key rotation receipt integrity verification failed';
        $installed=$this->installation->metadata()??[];$old=strtolower((string)($installed['key_fingerprint']??''));$current=$this->environment->current();$new=(string)($current['active_key_fingerprint']??'');
        if($old===''||preg_match('/^[a-f0-9]{64}$/',$old)!==1)$errors[]='installed key fingerprint unavailable';
        if($new===''||preg_match('/^[a-f0-9]{64}$/',$new)!==1)$errors[]='current APP_KEY fingerprint unavailable';
        if(($receipt['old_key_fingerprint']??null)!==$old)$errors[]='key rotation old-key fingerprint no longer matches installed metadata';
        if(($receipt['new_key_fingerprint']??null)!==$new)$errors[]='key rotation new-key fingerprint no longer matches current APP_KEY';
        if(($receipt['new_environment_fingerprint']??null)!==$current['fingerprint'])$errors[]='key rotation runtime environment fingerprint changed since authorization';
        if((bool)config('nexora-runtime.deployment.key_rotation_require_previous_key',true)&&!in_array($old,(array)$current['previous_key_fingerprints'],true))$errors[]='APP_PREVIOUS_KEYS does not contain the installed active key required for continuity';
        $expires=strtotime((string)($receipt['expires_at']??''));if($expires===false||$expires<$this->clock->databaseNow()->getTimestamp())$errors[]='key rotation receipt expired';
        if((bool)config('nexora-runtime.deployment.key_rotation_require_maintenance',true)&&!app()->isDownForMaintenance())$errors[]='key rotation authorization is valid only while shared maintenance mode remains active';
        return array_values(array_unique($errors));
    }

    /** @return array<string,mixed> */
    public function record(string $operator): array
    {
        $operator=trim($operator);if($operator===''||in_array(strtolower($operator),['operator','operator-name','your name'],true))throw new \RuntimeException('A real key-rotation operator identity is required.');
        if((bool)config('nexora-runtime.deployment.key_rotation_require_maintenance',true)&&!app()->isDownForMaintenance())throw new \RuntimeException('Enter shared application maintenance mode before authorizing APP_KEY rotation.');
        if(is_file($this->path()))throw new \RuntimeException('An active key-rotation receipt already exists. Commit or abort it before recording another rotation.');
        $installed=$this->installation->metadata()??[];$old=strtolower((string)($installed['key_fingerprint']??''));$current=$this->environment->current();$new=(string)($current['active_key_fingerprint']??'');
        if($old===''||$new==='')throw new \RuntimeException('Installed/current APP_KEY fingerprints are required.');
        if(hash_equals($old,$new))throw new \RuntimeException('APP_KEY has not changed; no key rotation is present to authorize.');
        if((bool)config('nexora-runtime.deployment.key_rotation_require_previous_key',true)&&!in_array($old,(array)$current['previous_key_fingerprints'],true))throw new \RuntimeException('APP_PREVIOUS_KEYS must contain the previous installed APP_KEY before rotation can be authorized.');
        $ttl=max(15,(int)config('nexora-runtime.deployment.key_rotation_receipt_ttl_minutes',120));
        $payload=['schema'=>1,'status'=>'authorized','rotation_id'=>(string)Str::uuid(),'old_key_fingerprint'=>$old,'new_key_fingerprint'=>$new,'new_environment_fingerprint'=>$current['fingerprint'],'previous_key_count'=>count((array)$current['previous_key_fingerprints']),'operator_sha256'=>hash('sha256',$operator),'created_at'=>$this->clock->databaseNow()->toIso8601String(),'expires_at'=>$this->clock->databaseNow()->copy()->addMinutes($ttl)->toIso8601String()];
        $payload['receipt_sha256']=$this->seal($payload);$this->write($this->path(),$payload);return $payload;
    }

    /** @return array{authorized:bool,errors:list<string>} */
    public function authorizesEnvironmentTransition(): array
    {
        $errors=$this->validate();return ['authorized'=>$errors===[],'errors'=>$errors];
    }

    /** @return array<string,mixed> */
    public function clusterStatus(): array
    {
        $current=$this->environment->current();$rows=[];$bad=[];
        if(Schema::hasTable('nx_runtime_nodes')){
            $fresh=max(30,(int)config('nexora-ha.fresh_node_seconds',180));
            foreach(RuntimeNode::query()->where('last_heartbeat_at','>=',$this->clock->databaseNow()->copy()->subSeconds($fresh))->orderBy('node_key')->get() as $node){$meta=is_array($node->metadata)?$node->metadata:[];$fp=strtolower((string)($meta['runtime_environment_fingerprint']??''));$row=['node_key'=>(string)$node->node_key,'status'=>(string)$node->status,'environment_fingerprint'=>$fp!==''?$fp:null];$rows[]=$row;if($fp===''||!hash_equals((string)$current['fingerprint'],$fp)||$row['status']==='active')$bad[]=$row;}
        }
        return ['status'=>$bad===[]?'pass':'pending','target_environment_fingerprint'=>$current['fingerprint'],'fresh_nodes'=>$rows,'not_converged'=>$bad,'maintenance_mode'=>app()->isDownForMaintenance()];
    }

    /** @return array<string,mixed> */
    public function commit(string $operator): array
    {
        $operator=trim($operator);if($operator===''||in_array(strtolower($operator),['operator','operator-name','your name'],true))throw new \RuntimeException('A real key-rotation operator identity is required.');
        $receipt=$this->read();$errors=$this->validate($receipt);if($errors!==[])throw new \RuntimeException('Key rotation cannot be committed: '.implode('; ',$errors));
        $cluster=$this->clusterStatus();if((bool)config('nexora-runtime.deployment.key_rotation_cluster_convergence_required',true)&&($cluster['status']??null)!=='pass')throw new \RuntimeException('All fresh runtime nodes must advertise the new environment fingerprint and remain drained/maintenance before key rotation commit.');
        $current=$this->environment->current();$receiptHash=hash_file('sha256',$this->path())?:null;
        $this->installation->updateMetadata(['key_fingerprint'=>$current['active_key_fingerprint'],'runtime_environment_fingerprint'=>$current['fingerprint'],'last_key_rotation_id'=>$receipt['rotation_id']??null,'last_key_rotation_receipt_sha256'=>$receiptHash,'key_rotated_at'=>$this->clock->databaseNow()->toIso8601String()]);
        $archived=$this->archive('committed',$receipt,['committed_at'=>$this->clock->databaseNow()->toIso8601String(),'commit_operator_sha256'=>hash('sha256',$operator)]);@unlink($this->path());return ['status'=>'committed','archive'=>$archived,'runtime_environment_fingerprint'=>$current['fingerprint'],'maintenance_mode_unchanged'=>true];
    }

    /** @return array<string,mixed> */
    public function abort(string $reason='operator abort'): array
    {
        $receipt=$this->read();if(!is_array($receipt))return ['status'=>'absent'];
        $archived=$this->archive('aborted',$receipt,['aborted_at'=>$this->clock->databaseNow()->toIso8601String(),'reason'=>substr(trim($reason),0,500)]);@unlink($this->path());return ['status'=>'aborted','archive'=>$archived,'configuration_rollback_performed'=>false];
    }

    /** @param array<string,mixed> $payload */
    private function seal(array $payload): string { unset($payload['receipt_sha256']);ksort($payload);return hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    /** @param array<string,mixed> $payload */
    private function write(string $path,array $payload): void {$dir=dirname($path);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Unable to create key-rotation directory.');$this->files->write($path,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);}
    /** @param array<string,mixed> $receipt @param array<string,mixed> $extra */
    private function archive(string $status,array $receipt,array $extra): string {$dir=$this->historyPath();if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Unable to create key-rotation history directory.');$payload=[...$receipt,...$extra,'status'=>$status];$payload['archive_sha256']=$this->seal($payload);$path=$dir.'/'.gmdate('YmdHis').'-'.preg_replace('/[^A-Za-z0-9._-]/','-',(string)($receipt['rotation_id']??'rotation')).'-'.$status.'.json';$this->write($path,$payload);return $path;}
}
