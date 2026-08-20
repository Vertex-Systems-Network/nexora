<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Foundation\Runtime\RuntimeLimitsDoctor;
use App\Nexora\Foundation\Runtime\RuntimeWritableTempDirectory;

final class RuntimeResourceEnvelopeIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private ?array $memo=null;

    public function __construct(
        private RuntimeLimitsDoctor $limits,
        private RuntimeWritableTempDirectory $tempDirectories,
    ) {}

    /** @return array<string,mixed> */
    public function current(bool $deep=false): array
    {
        if(!$deep&&is_array($this->memo)) return $this->memo;
        $materials=$this->policyMaterials();$fingerprint=$this->hash($materials);$doctor=$this->limits->inspect();
        $payload=[
            'schema'=>1,
            'status'=>($doctor['status']??'fail')==='pass'?'pass':'fail',
            'fingerprint'=>$fingerprint,
            'materials'=>$materials,
            'limits_status'=>$doctor['status']??'fail',
            'limits_checks'=>$doctor['checks']??[],
            'snapshot'=>$this->lightSnapshot(),
        ];
        if($deep){$payload['deep']=$this->deepProbe();if(($payload['deep']['status']??null)!=='pass')$payload['status']='fail';}
        if(!$deep)$this->memo=$payload;
        return $payload;
    }

    public function fingerprintValue(): string { return (string)$this->current(false)['fingerprint']; }

    /** @return array<string,mixed> */
    public function assertUpgradeCapacity(): array
    {
        $state=$this->current(true);
        if((bool)config('nexora-resource-runtime.require_deep_capacity_for_upgrade',true)&&($state['status']??null)!=='pass'){
            throw new \RuntimeException('Runtime resource envelope is not PASS; refuse upgrade until memory/disk/process headroom is restored.');
        }
        return $state;
    }

    /** @return array<string,mixed> */
    public function assertBackupScratchCapacity(): array
    {
        $state=$this->current(true);$checks=(array)($state['deep']['checks']??[]);
        foreach(['memory_headroom','transfer_free_space','storage_free_space','backup_staging_free_space'] as $key){
            if(($checks[$key]??false)!==true) throw new \RuntimeException('Runtime backup admission refused because resource check ['.$key.'] is not PASS.');
        }
        return $state;
    }

    /** @return array<string,mixed> */
    public function installationAttestation(): array
    {
        $strict = $this->current(true);
        $probe = $this->installationProbe();
        $blocking = [];

        foreach ($probe['checks'] as $name => $ok) {
            if ($ok !== true) {
                $blocking[] = $this->installationFailureReason($name, $probe['details']);
            }
        }

        $warnings = [];
        if (($strict['status'] ?? 'fail') !== 'pass') {
            $warnings[] = 'Strict resource/capacity certification is not PASS yet; C2/C6 still require the full production envelope.';
        }

        return [
            ...$strict,
            'installation_status' => $blocking === [] ? 'pass' : 'fail',
            'installation_checks' => $probe['checks'],
            'installation_details' => $probe['details'],
            'installation_blocking_reasons' => $blocking,
            'installation_warnings' => $warnings,
        ];
    }

    /** @return array{checks:array<string,bool>,details:array<string,mixed>} */
    private function installationProbe(): array
    {
        $checks = [];
        $details = [];

        $limit = $this->iniBytes('memory_limit');
        $usage = memory_get_usage(true);
        $headroom = $limit === PHP_INT_MAX ? PHP_INT_MAX : max(0, $limit - $usage);
        $requiredMemory = (int) config(
            'nexora-resource-runtime.installation_minimum_memory_headroom_bytes',
            33_554_432,
        );
        $checks['memory_headroom'] = $limit === PHP_INT_MAX || $headroom >= $requiredMemory;
        $details['memory'] = [
            'limit_bytes' => $limit === PHP_INT_MAX ? null : $limit,
            'unlimited' => $limit === PHP_INT_MAX,
            'usage_bytes' => $usage,
            'headroom_bytes' => $headroom === PHP_INT_MAX ? null : $headroom,
            'required_headroom_bytes' => $requiredMemory,
        ];

        $temp = $this->tempDirectories->installation();
        $details['temp_resolution'] = $temp;
        $selectedTemp = is_string($temp['selected_path'] ?? null)
            ? (string) $temp['selected_path']
            : $this->tempDirectories->systemPath();

        $paths = [
            'temp' => [
                $selectedTemp,
                (int) config('nexora-resource-runtime.installation_minimum_temp_free_bytes', 67_108_864),
            ],
            'storage' => [
                storage_path(),
                (int) config('nexora-resource-runtime.installation_minimum_storage_free_bytes', 134_217_728),
            ],
            'bootstrap' => [
                base_path('bootstrap/cache'),
                (int) config('nexora-resource-runtime.installation_minimum_bootstrap_free_bytes', 67_108_864),
            ],
        ];

        foreach ($paths as $name => [$path, $minimum]) {
            $capacity = $this->capacityFor((string) $path, (int) $minimum);
            $checks[$name.'_free_space'] = $capacity['ok'];
            $details[$name] = $capacity;
        }

        return ['checks' => $checks, 'details' => $details];
    }

    /** @param array<string,mixed> $details */
    private function installationFailureReason(string $name, array $details): string
    {
        if ($name === 'memory_headroom') {
            $memory = (array) ($details['memory'] ?? []);
            return sprintf(
                'PHP memory headroom is below the installer safety floor [%s available; %s required].',
                $this->formatBytes($memory['headroom_bytes'] ?? null),
                $this->formatBytes($memory['required_headroom_bytes'] ?? null),
            );
        }

        $pathName = str_replace('_free_space', '', $name);
        $capacity = (array) ($details[$pathName] ?? []);
        if ($pathName === 'temp' && ($capacity['writable'] ?? false) !== true) {
            $resolution = (array) ($details['temp_resolution'] ?? []);
            return sprintf(
                'No writable installation temp directory has enough capacity. Selected path: %s; system temp: %s.',
                (string) ($capacity['path'] ?? 'unknown'),
                (string) ($resolution['system_path'] ?? 'unknown'),
            );
        }
        return sprintf(
            '%s filesystem does not have the required writable free space [%s available; %s required; path %s].',
            ucfirst($pathName),
            $this->formatBytes($capacity['free_bytes'] ?? null),
            $this->formatBytes($capacity['minimum_free_bytes'] ?? null),
            (string) ($capacity['path'] ?? 'unknown'),
        );
    }

    private function formatBytes(mixed $value): string
    {
        if (! is_numeric($value)) {
            return 'unavailable';
        }

        $bytes = (int) $value;
        if ($bytes >= 1_073_741_824) {
            return number_format($bytes / 1_073_741_824, 2).' GiB';
        }
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1).' MiB';
        }

        return number_format($bytes).' bytes';
    }

    /** @return array<string,mixed> */
    private function policyMaterials(): array
    {
        return [
            'resource_policy_sha256'=>$this->policyHash(),
            'runtime_policy_sha256'=>$this->hashFile(config_path('nexora-runtime.php')),
            'minimum_memory_headroom_bytes'=>(int)config('nexora-resource-runtime.minimum_memory_headroom_bytes',134_217_728),
            'minimum_queue_memory_headroom_bytes'=>(int)config('nexora-resource-runtime.minimum_queue_memory_headroom_bytes',67_108_864),
            'minimum_temp_free_bytes'=>(int)config('nexora-resource-runtime.minimum_temp_free_bytes',536_870_912),
            'minimum_storage_free_bytes'=>(int)config('nexora-resource-runtime.minimum_storage_free_bytes',1_073_741_824),
            'minimum_transfer_free_bytes'=>(int)config('nexora-resource-runtime.minimum_transfer_free_bytes',1_073_741_824),
            'minimum_bootstrap_free_bytes'=>(int)config('nexora-resource-runtime.minimum_bootstrap_free_bytes',268_435_456),
            'minimum_backup_staging_free_bytes'=>(int)config('nexora-resource-runtime.minimum_backup_staging_free_bytes',1_073_741_824),
            'minimum_open_files_soft'=>(int)config('nexora-resource-runtime.minimum_open_files_soft',1024),
            'worker_restart_memory_mb'=>(int)config('nexora-runtime.queue.worker_restart_memory_mb',384),
        ];
    }

    /** @return array<string,mixed> */
    private function lightSnapshot(): array
    {
        $limit=$this->iniBytes('memory_limit');$usage=memory_get_usage(true);$worker=max(128,(int)config('nexora-runtime.queue.worker_restart_memory_mb',384))*1024*1024;
        return [
            'memory_limit_bytes'=>$limit===PHP_INT_MAX?null:$limit,
            'memory_unlimited'=>$limit===PHP_INT_MAX,
            'process_memory_bytes'=>$usage,
            'queue_worker_restart_bytes'=>$worker,
            'max_execution_time'=>(int)ini_get('max_execution_time'),
            'max_input_time'=>(int)ini_get('max_input_time'),
            'max_input_vars'=>(int)ini_get('max_input_vars'),
            'max_file_uploads'=>(int)ini_get('max_file_uploads'),
        ];
    }

    /** @return array<string,mixed> */
    private function deepProbe(): array
    {
        $checks=[];$details=[];
        $doctor=$this->limits->inspect();$checks['runtime_limits']=($doctor['status']??null)==='pass';$details['runtime_limits']=$doctor;
        $limit=$this->iniBytes('memory_limit');$usage=memory_get_usage(true);$headroom=$limit===PHP_INT_MAX?PHP_INT_MAX:max(0,$limit-$usage);$required=(int)config('nexora-resource-runtime.minimum_memory_headroom_bytes',134_217_728);
        $checks['memory_headroom']=$limit===PHP_INT_MAX||$headroom>=$required;$details['memory']=['limit_bytes'=>$limit===PHP_INT_MAX?null:$limit,'unlimited'=>$limit===PHP_INT_MAX,'usage_bytes'=>$usage,'headroom_bytes'=>$headroom===PHP_INT_MAX?null:$headroom,'required_headroom_bytes'=>$required];
        $paths=[
            'temp'=>[$this->tempDirectories->systemPath(),(int)config('nexora-resource-runtime.minimum_temp_free_bytes',536_870_912)],
            'storage'=>[storage_path(),(int)config('nexora-resource-runtime.minimum_storage_free_bytes',1_073_741_824)],
            'transfer'=>[(string)config('nexora-transfers.temporary_root',storage_path('app/nexora/transfers')),(int)config('nexora-resource-runtime.minimum_transfer_free_bytes',1_073_741_824)],
            'bootstrap'=>[base_path('bootstrap/cache'),(int)config('nexora-resource-runtime.minimum_bootstrap_free_bytes',268_435_456)],
            'backup_staging'=>[storage_path('app/nexora/database-backups'),(int)config('nexora-resource-runtime.minimum_backup_staging_free_bytes',1_073_741_824)],
        ];
        foreach($paths as $name=>[$path,$minimum]){
            $probe=$this->capacityFor((string)$path,(int)$minimum);$checks[$name.'_free_space']=$probe['ok'];$details[$name]=$probe;
        }
        $nofile=$this->openFilesLimit();$requiredNofile=(int)config('nexora-resource-runtime.minimum_open_files_soft',1024);$mustObserve=(bool)config('nexora-resource-runtime.require_open_files_observation_on_posix',false)&&PHP_OS_FAMILY!=='Windows';
        $checks['open_files_soft']=($nofile['soft']===null)?!$mustObserve:($nofile['soft']>=$requiredNofile);$details['open_files']=$nofile+['required_soft'=>$requiredNofile,'observation_required'=>$mustObserve];
        $worker=max(128,(int)config('nexora-runtime.queue.worker_restart_memory_mb',384))*1024*1024;$queueHeadroom=max(0,$worker-$usage);$queueRequired=(int)config('nexora-resource-runtime.minimum_queue_memory_headroom_bytes',67_108_864);
        $checks['queue_memory_headroom']=$usage<$worker&&$queueHeadroom>=$queueRequired;$details['queue_memory']=['usage_bytes'=>$usage,'worker_restart_bytes'=>$worker,'headroom_bytes'=>$queueHeadroom,'required_headroom_bytes'=>$queueRequired];
        $payload=['status'=>in_array(false,$checks,true)?'fail':'pass','checks'=>$checks,'details'=>$details];$payload['deep_sha256']=$this->hash($payload);return $payload;
    }

    /** @return array{path:string,free_bytes:?int,minimum_free_bytes:int,writable:bool,ok:bool,observable:bool} */
    private function capacityFor(string $path,int $minimum): array
    {
        $resolved=$this->existingParent($path);$writable=is_writable($resolved);$free=@disk_free_space($resolved);$observable=is_float($free)||is_int($free);$freeBytes=$observable?(int)$free:null;
        return ['path'=>$resolved,'free_bytes'=>$freeBytes,'minimum_free_bytes'=>$minimum,'writable'=>$writable,'ok'=>$writable&&$observable&&$freeBytes>=$minimum,'observable'=>$observable];
    }

    private function existingParent(string $path): string
    {
        $candidate=$path;while($candidate!==''&&!file_exists($candidate)){ $parent=dirname($candidate);if($parent===$candidate)break;$candidate=$parent; }
        return $candidate!==''&&file_exists($candidate)?$candidate:base_path();
    }

    /** @return array{soft:?int,hard:?int,observable:bool,source:string} */
    private function openFilesLimit(): array
    {
        if(function_exists('posix_getrlimit')){
            $limits=@posix_getrlimit();if(is_array($limits)){
                $soft=$limits['soft openfiles']??$limits['soft open files']??null;$hard=$limits['hard openfiles']??$limits['hard open files']??null;
                return ['soft'=>is_numeric($soft)?(int)$soft:null,'hard'=>is_numeric($hard)?(int)$hard:null,'observable'=>is_numeric($soft),'source'=>'posix_getrlimit'];
            }
        }
        if(PHP_OS_FAMILY==='Linux'&&is_readable('/proc/self/limits')){
            foreach(file('/proc/self/limits',FILE_IGNORE_NEW_LINES)?:[] as $line){if(preg_match('/^Max open files\s+(\d+|unlimited)\s+(\d+|unlimited)/i',$line,$m)===1){$soft=strtolower($m[1])==='unlimited'?PHP_INT_MAX:(int)$m[1];$hard=strtolower($m[2])==='unlimited'?PHP_INT_MAX:(int)$m[2];return ['soft'=>$soft,'hard'=>$hard,'observable'=>true,'source'=>'proc-self-limits'];}}
        }
        return ['soft'=>null,'hard'=>null,'observable'=>false,'source'=>'unavailable'];
    }

    private function iniBytes(string $key): int
    {
        $raw=trim((string)ini_get($key));if($raw===''||$raw==='-1')return $raw==='-1'?PHP_INT_MAX:0;if(ctype_digit($raw))return (int)$raw;$unit=strtolower(substr($raw,-1));$value=(float)substr($raw,0,-1);return (int)round($value*match($unit){'g'=>1024**3,'m'=>1024**2,'k'=>1024,default=>1});
    }
    private function policyHash(): ?string { return $this->hashFile(config_path('nexora-resource-runtime.php')); }
    private function hashFile(string $path): ?string { return is_file($path)?(hash_file('sha256',$path)?:null):null; }
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string { return hash('sha256',json_encode($this->canonicalize($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function canonicalize(mixed $value): mixed { if(!is_array($value))return $value;if(array_is_list($value))return array_map(fn(mixed $v):mixed=>$this->canonicalize($v),$value);ksort($value,SORT_STRING);foreach($value as $k=>$v)$value[$k]=$this->canonicalize($v);return $value; }
}
