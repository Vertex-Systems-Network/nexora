<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

final class RuntimeEnvironmentIdentity
{
    private ?array $memo=null;

    /** @return array<string,mixed> */
    public function current(): array
    {
        if(is_array($this->memo)) return $this->memo;
        $materials=$this->materials();
        $fingerprint=$this->fingerprint($materials);
        return $this->memo=[
            'schema'=>1,
            'fingerprint'=>$fingerprint,
            'materials'=>$materials,
            'active_key_fingerprint'=>$this->keyFingerprint((string)config('app.key','')),
            'previous_key_fingerprints'=>$this->previousKeyFingerprints(),
        ];
    }

    public function fingerprintValue(): string { return (string)$this->current()['fingerprint']; }
    public function activeKeyFingerprint(): ?string { return $this->current()['active_key_fingerprint']??null; }
    public function forgetMemoizedIdentity(): void { $this->memo=null; }

    /** @return list<string> */
    public function previousKeyFingerprints(): array
    {
        $rows=[];
        foreach((array)config('app.previous_keys',[]) as $key){$fp=$this->keyFingerprint((string)$key);if(is_string($fp))$rows[]=$fp;}
        $rows=array_values(array_unique($rows));sort($rows,SORT_STRING);return $rows;
    }

    /** @return array<string,mixed> */
    public function publicStatus(): array
    {
        $c=$this->current();
        return [
            'schema'=>$c['schema'],
            'fingerprint'=>$c['fingerprint'],
            'active_key_fingerprint'=>$c['active_key_fingerprint'],
            'previous_key_count'=>count((array)$c['previous_key_fingerprints']),
            'materials'=>$c['materials'],
        ];
    }

    /** @return array<string,mixed> */
    private function materials(): array
    {
        $url=parse_url((string)config('app.url',''))?:[];$origin='';
        if(isset($url['scheme'],$url['host'])){$origin=strtolower((string)$url['scheme']).'://'.strtolower((string)$url['host']).(isset($url['port'])?':'.(int)$url['port']:'');}
        $connection=(string)config('database.default','');
        return [
            'app_key_fingerprint'=>$this->keyFingerprint((string)config('app.key','')),
            'cipher'=>(string)config('app.cipher',''),
            'app_origin'=>$origin,
            'database_default'=>$connection,
            'database_driver'=>(string)config('database.connections.'.$connection.'.driver',''),
            'session_driver'=>(string)config('session.driver',''),
            'session_connection'=>(string)config('session.connection',''),
            'session_store'=>(string)config('session.store',''),
            'session_table'=>(string)config('session.table',''),
            'session_cookie'=>(string)config('session.cookie',''),
            'session_domain'=>(string)config('session.domain',''),
            'session_path'=>(string)config('session.path','/'),
            'session_secure'=>(bool)config('session.secure',false),
            'session_http_only'=>(bool)config('session.http_only',true),
            'session_same_site'=>(string)config('session.same_site',''),
            'session_partitioned'=>(bool)config('session.partitioned',false),
            'session_encrypt'=>(bool)config('session.encrypt',true),
            'session_serialization'=>(string)config('session.serialization','json'),
            'session_schema'=>max(1,(int)config('nexora-runtime.deployment.session_schema',1)),
            'cache_default'=>(string)config('cache.default',''),
            'cache_prefix'=>(string)config('cache.prefix',''),
            'queue_default'=>(string)config('queue.default',''),
            'filesystem_default'=>(string)config('filesystems.default',''),
            'object_storage_disk'=>(string)config('nexora_cloud.object_storage_disk',''),
            'maintenance_driver'=>(string)config('app.maintenance.driver',''),
            'maintenance_store'=>(string)config('app.maintenance.store',''),
        ];
    }

    /** @param array<string,mixed> $materials */
    private function fingerprint(array $materials): string
    {
        ksort($materials);return hash('sha256',json_encode($materials,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    private function keyFingerprint(string $key): ?string
    {
        $key=trim($key);if($key==='')return null;$raw=$key;
        if(str_starts_with($key,'base64:')){$decoded=base64_decode(substr($key,7),true);if(is_string($decoded)&&$decoded!=='')$raw=$decoded;}
        return hash('sha256',$raw);
    }
}
