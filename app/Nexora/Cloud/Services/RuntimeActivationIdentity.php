<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Installation\InstallationState;

final class RuntimeActivationIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private string $processEpoch;

    public function __construct(
        private readonly InstallationState $installation,
        private readonly RuntimeDeploymentIdentity $deployment,
        private readonly AtomicFileWriter $files,
    ) {
        $this->processEpoch=$this->resolveEpoch();
    }

    public function processEpoch(): string { return $this->processEpoch; }
    public function currentEpoch(): string { return $this->resolveEpoch(); }
    public function adoptCurrentEpochForProcess(): void { $this->processEpoch=$this->resolveEpoch(); }

    /** @return array<string,mixed> */
    public function current(): array
    {
        $epoch=$this->currentEpoch();$cache=$this->cacheSnapshot();$deployment=$this->deployment->current();
        $materials=[
            'schema'=>1,
            'activation_epoch'=>$epoch,
            'deployment_generation'=>(string)$deployment['generation'],
            'framework_cache_sha256'=>$cache['snapshot_sha256'],
            'php_runtime'=>PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
        ];
        $fingerprint=$this->fingerprint($materials);
        return ['schema'=>1,'activation_epoch'=>$epoch,'process_epoch'=>$this->processEpoch,'activation_fingerprint'=>$fingerprint,'materials'=>$materials,'framework_cache'=>$cache,'opcache'=>$this->opcacheStatus()];
    }

    /** @return array<string,mixed> */
    public function publicStatus(): array
    {
        $current=$this->current();$installed=$this->installation->metadata()??[];
        $installedEpoch=strtolower(trim((string)($installed['activation_epoch']??'')));$installedFp=strtolower(trim((string)($installed['runtime_activation_fingerprint']??'')));
        $legacy=$installedEpoch===''&&$installedFp==='';
        $epochCompatible=$legacy||($installedEpoch!==''&&hash_equals($installedEpoch,$current['activation_epoch']));
        $processCompatible=$legacy||hash_equals($current['activation_epoch'],$current['process_epoch']);
        $fingerprintCompatible=$legacy||($installedFp!==''&&hash_equals($installedFp,$current['activation_fingerprint']));
        return [
            'schema'=>1,
            'status'=>$epochCompatible&&$processCompatible&&$fingerprintCompatible?'pass':'fail',
            'legacy_compatibility'=>$legacy,
            'current'=>$current,
            'installed_activation_epoch'=>$installedEpoch!==''?$installedEpoch:null,
            'installed_activation_fingerprint'=>$installedFp!==''?$installedFp:null,
            'epoch_compatible'=>$epochCompatible,
            'process_epoch_compatible'=>$processCompatible,
            'fingerprint_compatible'=>$fingerprintCompatible,
        ];
    }

    /** @return array<string,mixed> */
    public function installationAttestation(): array
    {
        $blocking = [];
        $warnings = [];
        $epochPath = $this->epochPath();
        $directory = dirname($epochPath);

        try {
            $existing = $this->readEpochFile();
        } catch (\Throwable $exception) {
            $existing = null;
            $blocking[] = 'Existing runtime activation epoch is unreadable or failed integrity validation: '
                .$exception->getMessage();
        }

        if (! is_dir($directory)) {
            $parent = dirname($directory);
            if (! is_dir($parent) || ! is_writable($parent)) {
                $blocking[] = "Runtime activation directory cannot be created safely [{$directory}].";
            }
        } elseif (! is_writable($directory)) {
            $blocking[] = "Runtime activation directory is not writable [{$directory}].";
        }

        try {
            random_bytes(32);
            $secureRandom = true;
        } catch (\Throwable $exception) {
            $secureRandom = false;
            $blocking[] = 'Secure random generation is unavailable for the runtime activation epoch: '
                .$exception->getMessage();
        }

        $opcache = $this->opcacheStatus();
        if (($opcache['worker_restart_evidence_required'] ?? false) === true) {
            $warnings[] = 'OPcache timestamp validation is disabled; web-worker restart/source acknowledgement remains required.';
        }

        return [
            ...$this->current(),
            'installation_status' => $blocking === [] ? 'pass' : 'fail',
            'installation_checks' => [
                'existing_epoch_integrity' => $blocking === [] || $existing !== null || ! is_file($epochPath),
                'epoch_directory_writable' => is_dir($directory)
                    ? is_writable($directory)
                    : (is_dir(dirname($directory)) && is_writable(dirname($directory))),
                'secure_random_available' => $secureRandom,
            ],
            'installation_blocking_reasons' => $blocking,
            'installation_warnings' => $warnings,
            'existing_epoch' => $existing,
        ];
    }

    /** @return array<string,mixed> */
    public function bootstrap(string $reason='install'): array
    {
        $path=$this->epochPath();
        if(!is_file($path))$this->writeNewEpoch($reason,null);
        $this->adoptCurrentEpochForProcess();
        return $this->current();
    }

    /** @return array<string,mixed> */
    public function rotate(string $reason,string $operator): array
    {
        if((bool)config('nexora-activation.require_maintenance_for_manual_rotation',true)&&!app()->isDownForMaintenance()&&!str_starts_with($reason,'upgrade:'))throw new \RuntimeException('Manual runtime activation rotation requires maintenance mode.');
        $operator=trim($operator);if($operator===''||in_array(strtolower($operator),['operator','operator-name','your name'],true))throw new \RuntimeException('A real activation operator identity is required.');
        $previous=$this->readEpochFile();$this->writeNewEpoch($reason,$operator,$previous);$this->adoptCurrentEpochForProcess();return $this->current();
    }

    /** @return array<string,mixed> */
    public function cacheSnapshot(): array
    {
        $root=base_path();$paths=['bootstrap/cache/config.php','bootstrap/cache/events.php','bootstrap/cache/services.php','bootstrap/cache/packages.php','bootstrap/cache/nexora/runtime.php'];
        foreach(glob($root.'/bootstrap/cache/routes*.php')?:[] as $route){$paths[]=str_replace('\\','/',substr($route,strlen($root)+1));}
        $paths=array_values(array_unique($paths));sort($paths,SORT_STRING);$files=[];
        foreach($paths as $relative){$path=$root.'/'.$relative;$files[$relative]=is_file($path)?(hash_file('sha256',$path)?:null):null;}
        $snapshot=hash('sha256',json_encode($files,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return ['schema'=>1,'files'=>$files,'snapshot_sha256'=>$snapshot,'optimized_config'=>is_file($root.'/bootstrap/cache/config.php'),'route_cache_count'=>count(glob($root.'/bootstrap/cache/routes*.php')?:[])];
    }

    /** @return array<string,mixed> */
    public function opcacheStatus(): array
    {
        $enabled=extension_loaded('Zend OPcache')&&filter_var((string)ini_get('opcache.enable'),FILTER_VALIDATE_BOOL);$validate=filter_var((string)ini_get('opcache.validate_timestamps'),FILTER_VALIDATE_BOOL);$freq=max(0,(int)ini_get('opcache.revalidate_freq'));$max=max(0,(int)config('nexora-activation.opcache_revalidate_max_seconds',2));
        return ['enabled'=>$enabled,'validate_timestamps'=>$validate,'revalidate_freq'=>$freq,'live_reload_safe'=>!$enabled||($validate&&$freq<=$max),'worker_restart_evidence_required'=>$enabled&&!$validate,'sapi'=>PHP_SAPI];
    }

    /** @return array<string,mixed>|null */
    public function readEpochFile(): ?array
    {
        $path=$this->epochPath();if(!is_file($path))return null;try{$d=json_decode((string)file_get_contents($path),true,128,JSON_THROW_ON_ERROR);}catch(\Throwable){return null;}if(!is_array($d))return null;$expected=strtolower(trim((string)($d['record_sha256']??'')));$copy=$d;unset($copy['record_sha256']);ksort($copy);$actual=hash('sha256',json_encode($copy,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));if($expected===''||!hash_equals($expected,$actual))throw new \RuntimeException('Runtime activation epoch record integrity verification failed.');return $d;
    }

    private function resolveEpoch(): string
    {
        $file=$this->readEpochFile();$epoch=strtolower(trim((string)($file['activation_epoch']??'')));if(preg_match('/^[a-f0-9]{64}$/',$epoch)===1)return $epoch;
        $installed=$this->installation->metadata()??[];$epoch=strtolower(trim((string)($installed['activation_epoch']??'')));if(preg_match('/^[a-f0-9]{64}$/',$epoch)===1)return $epoch;
        return hash('sha256','legacy-activation|'.(string)config('nexora.version','unknown').'|'.$this->deployment->generation());
    }

    /** @param array<string,mixed>|null $previous */
    private function writeNewEpoch(string $reason,?string $operator,?array $previous=null): void
    {
        $payload=['schema'=>1,'status'=>'active','activation_epoch'=>bin2hex(random_bytes(32)),'reason'=>substr(trim($reason),0,120),'operator'=>$operator,'deployment_generation'=>$this->deployment->generation(),'created_at'=>now()->toIso8601String(),'previous_activation_epoch'=>$previous['activation_epoch']??null];
        $copy=$payload;ksort($copy);$payload['record_sha256']=hash('sha256',json_encode($copy,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if(is_array($previous))$this->archive($previous);
        $this->files->write($this->epochPath(),json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
    }

    /** @param array<string,mixed> $payload */
    private function archive(array $payload): void
    {
        $dir=(string)config('nexora-activation.history_path',base_path('storage/app/nexora/runtime/activation-history'));if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Unable to create runtime activation history directory.');$name=now()->format('YmdHis').'-'.substr((string)($payload['activation_epoch']??'epoch'),0,16).'.json';$this->files->write($dir.'/'.$name,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
    }

    private function epochPath(): string { return (string)config('nexora-activation.epoch_path',base_path('storage/app/nexora/runtime/activation-epoch.json')); }
    /** @param array<string,mixed> $materials */ private function fingerprint(array $materials): string { ksort($materials);return hash('sha256',json_encode($materials,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
}
