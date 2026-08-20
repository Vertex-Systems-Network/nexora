<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use Illuminate\Support\Facades\Storage;

final class RuntimeStorageDataPlaneIdentity
{
    /** @return array<string,mixed> */
    public function current(bool $deep=false): array
    {
        $roles=[
            'object'=>(string)config('nexora-storage-runtime.object_disk',config('nexora_cloud.object_storage_disk',config('filesystems.default','local'))),
            'media'=>(string)config('nexora-storage-runtime.media_disk','public'),
            'backup'=>(string)config('nexora-storage-runtime.backup_disk',config('nexora_cloud.object_storage_disk',config('filesystems.default','local'))),
        ];
        $profiles=[];
        foreach($roles as $role=>$disk)$profiles[$role]=$this->diskProfile($disk);
        $materials=[
            'schema'=>1,
            'namespace'=>(string)config('nexora-storage-runtime.namespace','nexora'),
            'cluster_id_sha256'=>$this->hashValue((string)config('nexora-storage-runtime.cluster_id','')),
            'roles'=>$profiles,
        ];
        $fingerprint=$this->hash($materials);
        $result=['schema'=>1,'status'=>'pass','fingerprint'=>$fingerprint,'namespace'=>$materials['namespace'],'cluster_id_sha256'=>$materials['cluster_id_sha256'],'roles'=>$profiles];
        if($deep){$deepResult=$this->deepInspect($profiles);$result['deep']=$deepResult;$result['status']=$deepResult['ok']?'pass':'fail';}
        return $result;
    }

    public function fingerprintValue(): string { return (string)$this->current(false)['fingerprint']; }

    /** @return array<string,mixed> */
    public function diskProfile(string $disk): array
    {
        $cfg=(array)config("filesystems.disks.{$disk}",[]);$driver=strtolower(trim((string)($cfg['driver']??'unknown')));
        $profile=['disk'=>$disk,'driver'=>$driver,'shared_candidate'=>in_array($driver,(array)config('nexora-storage-runtime.shared_drivers',[]),true)];
        if($driver==='local'){
            $root=(string)($cfg['root']??'');$normalized=str_replace('\\','/',rtrim($root,'/\\'));
            $profile['locator_sha256']=$this->hashValue($normalized);
            $profile['visibility']=(string)($cfg['visibility']??'private');
        }elseif($driver==='s3'){
            $endpoint=(string)($cfg['endpoint']??'');$host=$endpoint!==''?(string)(parse_url($endpoint,PHP_URL_HOST)?:$endpoint):'aws-default';
            $profile['bucket_sha256']=$this->hashValue((string)($cfg['bucket']??''));
            $profile['region']=strtolower((string)($cfg['region']??''));
            $profile['endpoint_sha256']=$this->hashValue(strtolower($host));
            $profile['root_sha256']=$this->hashValue(trim((string)($cfg['root']??''),' /\\'));
            $profile['path_style']=(bool)($cfg['use_path_style_endpoint']??false);
            $profile['visibility']=(string)($cfg['visibility']??'private');
        }elseif(in_array($driver,['ftp','sftp'],true)){
            $profile['host_sha256']=$this->hashValue(strtolower((string)($cfg['host']??'')));
            $profile['port']=isset($cfg['port'])?(int)$cfg['port']:null;
            $profile['root_sha256']=$this->hashValue(trim((string)($cfg['root']??''),' /\\'));
        }else{
            $profile['config_shape_sha256']=$this->hash($this->safeConfigShape($cfg));
        }
        $profile['profile_sha256']=$this->hash($profile);
        return $profile;
    }

    /** @param array<string,array<string,mixed>> $profiles @return array{ok:bool,checks:list<array<string,mixed>>,deep_sha256:string} */
    private function deepInspect(array $profiles): array
    {
        $checks=[];$seen=[];
        foreach($profiles as $role=>$profile){
            $disk=(string)$profile['disk'];
            if(isset($seen[$disk])){$checks[]=['key'=>$role.':roundtrip','status'=>'pass','detail'=>'disk already probed as '.$seen[$disk]];continue;}
            $seen[$disk]=$role;$path=trim((string)config('nexora-storage-runtime.deep_probe_prefix','nexora/runtime/storage-probes'),' /\\').'/'.bin2hex(random_bytes(10)).'.probe';$payload='nexora-storage-probe:'.bin2hex(random_bytes(16));
            try{
                $stored=Storage::disk($disk)->put($path,$payload,['visibility'=>'private']);
                if($stored!==true||!Storage::disk($disk)->exists($path))throw new \RuntimeException('write/exists verification failed');
                $read=Storage::disk($disk)->get($path);if(!is_string($read)||!hash_equals($payload,$read))throw new \RuntimeException('read-after-write verification failed');
                if(!Storage::disk($disk)->delete($path))throw new \RuntimeException('probe cleanup failed');
                if(Storage::disk($disk)->exists($path))throw new \RuntimeException('probe object remained after delete');
                $checks[]=['key'=>$role.':roundtrip','status'=>'pass','detail'=>'write/read/delete probe passed'];
            }catch(\Throwable $e){try{Storage::disk($disk)->delete($path);}catch(\Throwable){}$checks[]=['key'=>$role.':roundtrip','status'=>'fail','detail'=>$e->getMessage()];}
        }
        $media=$profiles['media']??[];
        if(($media['driver']??null)==='local'&&(bool)config('nexora-storage-runtime.require_public_link_if_local',true)){
            $link=public_path('storage');$target=storage_path('app/public');$linkReal=is_link($link)||is_dir($link)?realpath($link):false;$targetReal=realpath($target);
            $ok=is_string($linkReal)&&is_string($targetReal)&&$this->normalizePath($linkReal)===$this->normalizePath($targetReal);
            $checks[]=['key'=>'media:public-link','status'=>$ok?'pass':'fail','detail'=>$ok?'public/storage resolves to storage/app/public':'public/storage link/junction does not resolve to storage/app/public'];
        }
        $ok=!in_array('fail',array_map(static fn(array $c):string=>(string)$c['status'],$checks),true);$deep=['checks'=>$checks];$deepSha=$this->hash($deep);
        return ['ok'=>$ok,'checks'=>$checks,'deep_sha256'=>$deepSha];
    }

    /** @param array<string,mixed> $cfg @return array<string,mixed> */
    private function safeConfigShape(array $cfg): array
    {
        $secretKeys=['key','secret','password','token','username','privateKey','passphrase'];$out=[];
        foreach($cfg as $key=>$value){if(in_array((string)$key,$secretKeys,true))continue;if(is_scalar($value)||$value===null)$out[(string)$key]=is_string($value)&&strlen($value)>256?hash('sha256',$value):$value;}
        return $out;
    }

    private function hashValue(string $value): ?string { $value=trim($value);return $value===''?null:hash('sha256',$value); }
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string { return hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function canonical(mixed $v): mixed { if(!is_array($v))return $v;if(array_is_list($v))return array_map(fn($i)=>$this->canonical($i),$v);ksort($v,SORT_STRING);foreach($v as $k=>$i)$v[$k]=$this->canonical($i);return $v; }
    private function normalizePath(string $path): string { $path=str_replace('\\','/',$path);return PHP_OS_FAMILY==='Windows'?strtolower(rtrim($path,'/')):rtrim($path,'/'); }
}
