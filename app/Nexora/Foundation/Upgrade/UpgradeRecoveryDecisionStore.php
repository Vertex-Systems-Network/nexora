<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

final class UpgradeRecoveryDecisionStore
{
    private const DECISIONS=['restore_verified_backup','retry_pre_migration','manual_investigation'];
    public function __construct(private readonly AtomicFileWriter $files) {}
    public function path(): string { return (string)config('nexora-upgrade.recovery_decision_path',base_path('storage/app/nexora/upgrade/recovery-decision.json')); }

    /** @return array<string,mixed>|null */
    public function read(): ?array
    {
        if(!is_file($this->path())) return null;
        $payload=json_decode((string)file_get_contents($this->path()),true); if(!is_array($payload)) throw new \RuntimeException('Upgrade recovery decision is unreadable.');
        $expected=trim((string)($payload['decision_sha256']??'')); $actual=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if($expected===''||!hash_equals($expected,$actual)) throw new \RuntimeException('Upgrade recovery decision integrity verification failed.');
        $recorded=strtotime((string)($payload['recorded_at']??''));$ttl=(int)config('nexora-upgrade.recovery_decision_ttl_hours',168);
        $payload['expired']=$recorded===false||$recorded<time()-$ttl*3600;
        return $payload;
    }

    /** @return array<string,mixed> */
    public function record(array $journal,string $decision,string $operator,string $note=''): array
    {
        if(!in_array($decision,self::DECISIONS,true)) throw new \InvalidArgumentException('Unsupported recovery decision.');
        $operator=trim($operator); if($operator===''||in_array(strtolower($operator),['operator','operator-name','your name'],true)) throw new \RuntimeException('A real recovery operator identity is required.');
        $stage=(string)($journal['stage']??'');$mutationStages=['migrations_started','migrations_completed','runtime_sync_completed','runtime_cache_completed','post_upgrade_health_passed','installation_metadata_committing','installation_metadata_committed','post_metadata_health_passed','maintenance_disabled','completed'];
        if($decision==='retry_pre_migration'&&in_array($stage,$mutationStages,true)) throw new \RuntimeException('retry_pre_migration is forbidden after database/data mutation may have started. Restore the verified backup instead.');
        $existing=$this->read(); if(is_array($existing)) $this->archive($existing);
        $payload=[
            'schema'=>1,
            'status'=>'recorded',
            'upgrade_id'=>$journal['upgrade_id']??null,
            'journal_sha256'=>$journal['journal_sha256']??null,
            'stage'=>$stage,
            'decision'=>$decision,
            'operator'=>$operator,
            'note'=>substr(trim($note),0,1000),
            'recorded_at'=>now()->toIso8601String(),
            'automatic_recovery_executed'=>false,
        ];
        $payload['decision_sha256']=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->files->write($this->path(),json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
        return $payload;
    }


    /** @param array<string,mixed> $payload */
    private function archive(array $payload): void
    {
        $history=(string)config('nexora-upgrade.recovery_decision_history_path',base_path('storage/app/nexora/upgrade/recovery-decisions'));
        if(!is_dir($history)&&!mkdir($history,0755,true)&&!is_dir($history)) throw new \RuntimeException('Unable to create upgrade recovery-decision history directory.');
        $id=preg_replace('/[^0-9A-Za-z._-]/','-',(string)($payload['upgrade_id']??'upgrade'))?:'upgrade';
        $stamp=gmdate('Ymd-His');
        $target=$history.'/'.$id.'-'.$stamp.'-'.substr((string)($payload['decision_sha256']??'unknown'),0,12).'.json';
        $this->files->write($target,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonical(array $payload): array { unset($payload['decision_sha256']); ksort($payload); return $payload; }
}
