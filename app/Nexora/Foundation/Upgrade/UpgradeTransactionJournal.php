<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

final class UpgradeTransactionJournal
{
    public function __construct(private readonly AtomicFileWriter $files) {}

    public function path(): string
    {
        return (string) config('nexora-upgrade.transaction_journal_path', base_path('storage/app/nexora/upgrade/transaction.json'));
    }

    /** @return array<string,mixed>|null */
    public function read(): ?array
    {
        $path=$this->path();
        if(!is_file($path)) return null;
        try{$payload=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(\Throwable){throw new \RuntimeException('Upgrade transaction journal is unreadable. Recovery must be reviewed before another upgrade.');}
        if(!is_array($payload)) throw new \RuntimeException('Upgrade transaction journal must be a JSON object.');
        $expected=strtolower(trim((string)($payload['journal_sha256']??'')));
        $actual=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if($expected===''||!hash_equals($expected,$actual)) throw new \RuntimeException('Upgrade transaction journal integrity verification failed. Do not continue automatically.');
        return $payload;
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    public function begin(array $context): array
    {
        $existing=$this->read();
        if(is_array($existing)&&in_array((string)($existing['status']??''),['running','recovery_required'],true)){
            throw new \RuntimeException('An unfinished upgrade transaction journal exists. Review nexora:upgrade:recovery-status before another apply.');
        }
        $payload=[
            'schema'=>1,
            'status'=>'running',
            'stage'=>'prepared',
            'upgrade_id'=>$context['upgrade_id']??null,
            'source_version'=>$context['source_version']??null,
            'target_version'=>$context['target_version']??null,
            'trusted_update_receipt_sha256'=>$context['trusted_update_receipt_sha256']??null,
            'backup_type'=>$context['backup_type']??null,
            'backup_reference'=>$context['backup_reference']??null,
            'backup_sha256'=>$context['backup_sha256']??null,
            'backup_database_fingerprint'=>$context['backup_database_fingerprint']??null,
            'restore_plan_id'=>$context['restore_plan_id']??null,
            'restore_readiness_sha256'=>$context['restore_readiness_sha256']??null,
            'maintenance_lease_sha256'=>$context['maintenance_lease_sha256']??null,
            'cluster_lease_sha256'=>$context['cluster_lease_sha256']??null,
            'migration_ledger_before_sha256'=>$context['migration_ledger_before_sha256']??null,
            'compatibility_assessment_sha256'=>$context['compatibility_assessment_sha256']??null,
            'migration_safety_sha256'=>$context['migration_safety_sha256']??null,
            'maintenance_required'=>false,
            'started_at'=>now()->toIso8601String(),
            'updated_at'=>now()->toIso8601String(),
            'events'=>[],
        ];
        return $this->publish($payload);
    }

    /** @param array<string,mixed> $detail @return array<string,mixed> */
    public function checkpoint(string $stage,array $detail=[]): array
    {
        $payload=$this->read();if(!is_array($payload)||($payload['status']??null)!=='running')throw new \RuntimeException('No running upgrade transaction journal exists.');
        $events=is_array($payload['events']??null)?$payload['events']:[];
        $events[]=['stage'=>$stage,'at'=>now()->toIso8601String(),'detail'=>$this->sanitizeDetail($detail)];
        if(count($events)>64)$events=array_slice($events,-64);
        $payload['events']=$events;$payload['stage']=$stage;$payload['updated_at']=now()->toIso8601String();
        if($stage==='maintenance_enabled')$payload['maintenance_required']=true;
        if($stage==='maintenance_disabled')$payload['maintenance_required']=false;
        return $this->publish($payload);
    }

    /** @return array<string,mixed> */
    public function fail(string $stage,string $message): array
    {
        $payload=$this->read()??['schema'=>1,'events'=>[]];
        $payload['status']='recovery_required';$payload['stage']=$stage;$payload['maintenance_required']=true;$payload['failed_at']=now()->toIso8601String();$payload['updated_at']=now()->toIso8601String();
        $payload['failure']=substr(trim($message),0,1000);
        $payload['recovery_action']='Restore the verified source-version backup in a disposable/controlled recovery procedure before serving traffic. Do not attempt blind down-migrations.';
        return $this->publish($payload);
    }

    /** @return array<string,mixed> */
    public function abortPreMutation(string $stage,string $message): array
    {
        $payload=$this->read();if(!is_array($payload)||($payload['status']??null)!=='running')throw new \RuntimeException('No running upgrade transaction journal exists for pre-mutation abort.');
        $mutationStages=['migrations_started','migrations_completed','migration_ledger_converged','runtime_sync_completed','runtime_cache_completed','post_upgrade_health_passed','installation_metadata_committing','installation_metadata_committed','post_metadata_health_passed','maintenance_disabled','completed'];
        if(in_array((string)($payload['stage']??''),$mutationStages,true))throw new \RuntimeException('Pre-mutation abort refused after data/schema mutation may have started.');
        $payload['status']='aborted';$payload['stage']=$stage;$payload['maintenance_required']=false;$payload['aborted_at']=now()->toIso8601String();$payload['updated_at']=now()->toIso8601String();$payload['failure']=substr(trim($message),0,1000);$payload['recovery_action']='No schema mutation was recorded. Fix the preflight/quiescence issue, create a fresh plan, and retry; do not restore a backup solely for this aborted transaction.';
        return $this->publish($payload);
    }

    /** @return array<string,mixed> */
    public function complete(): array
    {
        $payload=$this->read();if(!is_array($payload))throw new \RuntimeException('Upgrade transaction journal missing during completion.');
        $payload['status']='completed';$payload['stage']='completed';$payload['maintenance_required']=false;$payload['completed_at']=now()->toIso8601String();$payload['updated_at']=now()->toIso8601String();
        return $this->publish($payload);
    }

    public function archiveAndClear(): ?string
    {
        $payload=$this->read();if(!is_array($payload))return null;
        $history=(string)config('nexora-upgrade.transaction_history_path',base_path('storage/app/nexora/upgrade/transaction-history'));
        if(!is_dir($history)&&!mkdir($history,0755,true)&&!is_dir($history))throw new \RuntimeException('Unable to create upgrade transaction-history directory.');
        $id=preg_replace('/[^0-9A-Za-z._-]/','-',(string)($payload['upgrade_id']??now()->format('YmdHis')))?:now()->format('YmdHis');
        $target=$history.'/'.$id.'-'.(string)($payload['status']??'unknown').'.json';
        $this->files->write($target,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
        if(is_file($this->path())&&!@unlink($this->path()))throw new \RuntimeException('Unable to clear active upgrade transaction journal after archival.');
        return $target;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function publish(array $payload): array
    {
        $payload['journal_sha256']=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->files->write($this->path(),json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
        return $payload;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonical(array $payload): array{unset($payload['journal_sha256']);ksort($payload);return $payload;}
    /** @param array<string,mixed> $detail @return array<string,mixed> */
    private function sanitizeDetail(array $detail): array{foreach($detail as $k=>$v){if(is_string($v))$detail[$k]=substr($v,0,500);elseif(!is_scalar($v)&&$v!==null)$detail[$k]='[non-scalar]';}return $detail;}
}
