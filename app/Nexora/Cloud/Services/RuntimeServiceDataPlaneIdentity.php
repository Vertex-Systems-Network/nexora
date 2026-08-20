<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

final class RuntimeServiceDataPlaneIdentity
{
    private ?array $memo=null;

    /** @return array<string,mixed> */
    public function current(bool $deep=false): array
    {
        if(!$deep&&is_array($this->memo))return $this->memo;
        $materials=$this->materials();$fingerprint=$this->hash($materials);
        $policyErrors=$this->policyErrors();$result=['schema'=>1,'status'=>$policyErrors===[]?'pass':'fail','fingerprint'=>$fingerprint,'materials'=>$materials,'policy_errors'=>$policyErrors,'deep'=>null];
        if($deep){$result['deep']=$this->deepProbe($materials);if(($result['deep']['status']??null)!=='pass')$result['status']='fail';}
        if(!$deep)$this->memo=$result;return $result;
    }

    public function fingerprintValue(): string { return (string)$this->current(false)['fingerprint']; }
    public function forgetMemoizedIdentity(): void { $this->memo=null; }

    /** @return array<string,mixed> */
    public function publicStatus(bool $deep=false): array { return $this->current($deep); }

    /** @return list<string> */
    private function policyErrors(): array
    {
        $errors=[];
        if(app()->environment('production')){
            if(!(bool)config('nexora-network-runtime.external.require_https',true))$errors[]='production external HTTPS enforcement is disabled';
            if(!(bool)config('nexora-network-runtime.external.block_private_reserved',true))$errors[]='production private/reserved destination blocking is disabled';
            if(!(bool)config('nexora-network-runtime.external.require_dns_resolution',true))$errors[]='production external DNS validation is disabled';
            if(!(bool)config('nexora-network-runtime.external.require_dns_pin',true))$errors[]='production DNS pinning is disabled';
            if(!(bool)config('nexora-network-runtime.tls.verify_peer',true))$errors[]='production TLS peer verification is disabled';
        }
        return $errors;
    }

    /** @return array<string,mixed> */
    private function materials(): array
    {
        $cache=(string)config('cache.default','');$session=(string)config('session.driver','');$queue=(string)config('queue.default','');$mailer=(string)config('mail.default','');
        $ca=$this->caBundleProfile();$proxy=$this->proxyProfile();
        $redis=[];foreach(['default','cache'] as $name){$cfg=config('database.redis.'.$name);if(is_array($cfg))$redis[$name]=$this->sanitize($cfg);}ksort($redis);
        $trusted=array_values(array_map('strval',(array)config('nexora-runtime.http.trusted_proxies',[])));sort($trusted,SORT_STRING);
        return [
            'cache'=>['store'=>$cache,'profile'=>$this->sanitize((array)config('cache.stores.'.$cache,[]))],
            'session'=>['driver'=>$session,'connection'=>(string)config('session.connection',''),'store'=>(string)config('session.store',''),'table'=>(string)config('session.table','')],
            'queue'=>['connection'=>$queue,'profile'=>$this->sanitize((array)config('queue.connections.'.$queue,[]))],
            'redis'=>['client'=>(string)config('database.redis.client',''),'cluster'=>(string)config('database.redis.options.cluster',''),'persistent'=>(bool)config('database.redis.options.persistent',false),'connections'=>$redis],
            'mail'=>['default'=>$mailer,'profile'=>$this->sanitize((array)config('mail.mailers.'.$mailer,[]))],
            'network'=>[
                'external_policy'=>[
                    'require_https'=>(bool)config('nexora-network-runtime.external.require_https',true),
                    'block_private_reserved'=>(bool)config('nexora-network-runtime.external.block_private_reserved',true),
                    'require_dns_resolution'=>(bool)config('nexora-network-runtime.external.require_dns_resolution',true),
                    'require_dns_pin'=>(bool)config('nexora-network-runtime.external.require_dns_pin',true),
                    'allowed_ports'=>array_values(array_map('intval',(array)config('nexora-network-runtime.external.allowed_ports',[443]))),
                ],
                'proxy'=>$proxy,
                'ca_bundle'=>$ca,
                'trusted_proxies_sha256'=>hash('sha256',json_encode($trusted,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),
                'trusted_proxies_count'=>count($trusted),
            ],
        ];
    }

    /** @param array<string,mixed> $materials @return array<string,mixed> */
    private function deepProbe(array $materials): array
    {
        $checks=[];$ok=true;
        if((bool)config('nexora-network-runtime.deep_probe.cache_roundtrip',true)){
            $key='nexora:service-probe:'.bin2hex(random_bytes(8));$value=bin2hex(random_bytes(16));$pass=false;$detail='';
            try{$store=Cache::store((string)($materials['cache']['store']??config('cache.default')));$store->put($key,$value,30);$pass=hash_equals($value,(string)$store->get($key));$store->forget($key);$detail=$pass?'write/read/delete pass':'cache readback mismatch';}catch(\Throwable $e){$detail=$e->getMessage();}
            $checks['cache_roundtrip']=['status'=>$pass?'pass':'fail','detail'=>substr($detail,0,500)];$ok=$ok&&$pass;
        }
        if((bool)config('nexora-network-runtime.deep_probe.redis_ping',true)){
            $needed=[];$cacheProfile=(array)($materials['cache']['profile']??[]);$queueProfile=(array)($materials['queue']['profile']??[]);if(($cacheProfile['driver']??null)==='redis')$needed[]=(string)($cacheProfile['connection']??'cache');if(($queueProfile['driver']??null)==='redis')$needed[]=(string)($queueProfile['connection']??'default');if(($materials['session']['driver']??null)==='redis')$needed[]=(string)(($materials['session']['connection']??'')?:'default');$needed=array_values(array_unique($needed));sort($needed);
            foreach($needed as $name){$pass=false;$detail='';try{$response=app('redis')->connection($name)->command('ping');$pass=$response!==false&&$response!==null;$detail=$pass?'PING pass':'PING returned empty response';}catch(\Throwable $e){$detail=$e->getMessage();}$checks['redis_'.$name]=['status'=>$pass?'pass':'fail','detail'=>substr($detail,0,500)];$ok=$ok&&$pass;}
        }
        if((bool)config('nexora-network-runtime.deep_probe.queue_size',true)){
            $pass=false;$detail='';try{$connection=(string)($materials['queue']['connection']??config('queue.default'));$queueName=(string)(($materials['queue']['profile']['queue']??'')?:'default');$size=Queue::connection($connection)->size($queueName);$pass=is_int($size)||is_numeric($size);$detail='queue='.$queueName.'; size='.(string)$size;}catch(\Throwable $e){$detail=$e->getMessage();}$checks['queue_visibility']=['status'=>$pass?'pass':'fail','detail'=>substr($detail,0,500)];$ok=$ok&&$pass;
        }
        if((bool)config('nexora-network-runtime.deep_probe.mail_dns',true)){
            $profile=(array)($materials['mail']['profile']??[]);$transport=(string)($profile['transport']??'');$host=trim((string)($profile['host']??''));
            if($transport==='smtp'&&$host!==''){$ips=$this->resolve($host);$pass=$ips!==[];$checks['mail_dns']=['status'=>$pass?'pass':'fail','detail'=>$pass?'resolved='.count($ips):'SMTP host DNS resolution failed'];$ok=$ok&&$pass;
                if($pass&&(bool)config('nexora-network-runtime.deep_probe.mail_tcp',false)){$port=max(1,(int)($profile['port']??25));$errno=0;$errstr='';$socket=@fsockopen($host,$port,$errno,$errstr,(float)config('nexora-network-runtime.deep_probe.mail_tcp_timeout_seconds',3));$tcp=is_resource($socket);if($tcp)fclose($socket);$checks['mail_tcp']=['status'=>$tcp?'pass':'fail','detail'=>$tcp?'TCP connect pass':'TCP connect failed: '.$errno.' '.$errstr];$ok=$ok&&$tcp;}
            }else{$checks['mail_dns']=['status'=>'pass','detail'=>'non-SMTP/default mail transport does not require SMTP DNS probe'];}
        }
        foreach((array)($materials['network']['ca_bundle']['files']??[]) as $index=>$row){$sha=(string)($row['sha256']??'');$pass=preg_match('/^[a-f0-9]{64}$/',$sha)===1;$checks['ca_bundle_'.$index]=['status'=>$pass?'pass':'fail','detail'=>$pass?'CA bundle readable and hashed':'CA bundle unavailable'];$ok=$ok&&$pass;}
        $canonical=$checks;ksort($canonical);return ['status'=>$ok?'pass':'fail','checks'=>$checks,'deep_sha256'=>hash('sha256',json_encode($canonical,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))];
    }

    /** @return array<string,mixed> */
    private function caBundleProfile(): array
    {
        $paths=[];$explicit=trim((string)config('nexora-network-runtime.tls.ca_bundle',''));if($explicit!=='')$paths[]=$explicit;foreach([(string)ini_get('openssl.cafile'),(string)ini_get('curl.cainfo')] as $path)if(trim($path)!=='')$paths[]=trim($path);$paths=array_values(array_unique($paths));$files=[];
        foreach($paths as $path){$files[]=['name'=>basename($path),'sha256'=>is_file($path)?(hash_file('sha256',$path)?:null):null,'readable'=>is_readable($path)];}
        usort($files,static fn(array $a,array $b):int=>strcmp((string)$a['name'],(string)$b['name']));return ['verify_peer'=>(bool)config('nexora-network-runtime.tls.verify_peer',true),'files'=>$files];
    }

    /** @return array<string,mixed> */
    private function proxyProfile(): array
    {
        $profile=[];foreach(['HTTP_PROXY','HTTPS_PROXY'] as $name){$value=(string)(getenv($name)?:getenv(strtolower($name))?:'');$profile[strtolower($name)]=$this->sanitizeUrl($value);} $no=(string)(getenv('NO_PROXY')?:getenv('no_proxy')?:'');$parts=array_values(array_filter(array_map('trim',explode(',',$no))));sort($parts,SORT_STRING);$profile['no_proxy_sha256']=hash('sha256',json_encode($parts,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));$profile['no_proxy_count']=count($parts);return $profile;
    }

    private function sanitizeUrl(string $url): ?string
    {
        $url=trim($url);if($url==='')return null;$parts=parse_url($url);if(!is_array($parts))return hash('sha256',$url);$scheme=strtolower((string)($parts['scheme']??''));$host=strtolower((string)($parts['host']??''));$port=isset($parts['port'])?':'.(int)$parts['port']:'';return ($scheme!==''?$scheme.'://':'').$host.$port;
    }

    private function sanitize(mixed $value,?string $key=null): mixed
    {
        if(is_array($value)){$out=[];foreach($value as $k=>$v){$name=strtolower((string)$k);if(preg_match('/password|secret|token|credential|access_key|secret_key|username/',$name))continue;$out[$k]=$this->sanitize($v,(string)$k);}if(!array_is_list($out))ksort($out);return $out;}
        if(is_string($value)&&$key!==null&&str_contains(strtolower($key),'url'))return $this->sanitizeUrl($value);
        if(is_object($value))return get_class($value);
        return $value;
    }

    /** @return list<string> */
    private function resolve(string $host): array { if(filter_var($host,FILTER_VALIDATE_IP))return [$host];$records=@dns_get_record($host,DNS_A|DNS_AAAA)?:[];$ips=[];foreach($records as $r){if(isset($r['ip']))$ips[]=(string)$r['ip'];if(isset($r['ipv6']))$ips[]=(string)$r['ipv6'];}$ips=array_values(array_unique(array_filter($ips,static fn(string $ip):bool=>filter_var($ip,FILTER_VALIDATE_IP)!==false)));sort($ips,SORT_STRING);return $ips; }
    /** @param array<string,mixed> $value */
    private function hash(array $value): string { $value=$this->canonical($value);return hash('sha256',json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function canonical(mixed $value): mixed { if(!is_array($value))return $value;if(array_is_list($value))return array_map(fn($v)=>$this->canonical($v),$value);ksort($value);foreach($value as $k=>$v)$value[$k]=$this->canonical($v);return $value; }
}
