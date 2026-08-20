<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

final class RuntimeEngineIdentity
{
    private ?array $memo=null;

    /** @return array<string,mixed> */
    public function current(): array
    {
        if(is_array($this->memo)) return $this->memo;
        $materials=$this->materials();$fingerprint=$this->fingerprint($materials);$process=$this->processProfile();
        return $this->memo=[
            'schema'=>1,
            'fingerprint'=>$fingerprint,
            'materials'=>$materials,
            'process_profile'=>$process,
            'process_profile_fingerprint'=>$this->fingerprint($process),
            'required_extensions_missing'=>$this->missingRequiredExtensions(),
        ];
    }

    public function fingerprintValue(): string { return (string)$this->current()['fingerprint']; }

    /** @return array<string,mixed> */
    public function publicStatus(bool $deep=false): array
    {
        $current=$this->current();
        return [
            'schema'=>1,
            'status'=>$current['required_extensions_missing']===[]?'pass':'fail',
            'fingerprint'=>$current['fingerprint'],
            'php_version'=>$current['materials']['php_version'],
            'php_version_id'=>$current['materials']['php_version_id'],
            'extension_profile_sha256'=>$current['materials']['extension_profile_sha256'],
            'pdo_drivers'=>$current['materials']['pdo_drivers'],
            'required_extensions_missing'=>$current['required_extensions_missing'],
            'process_profile_fingerprint'=>$current['process_profile_fingerprint'],
            'process_profile'=>$deep?$current['process_profile']:['sapi'=>$current['process_profile']['sapi'],'binary'=>$current['process_profile']['binary']],
            'materials'=>$deep?$current['materials']:null,
        ];
    }

    /** @return array<string,mixed> */
    private function materials(): array
    {
        $extensions=[];
        foreach((array)config('nexora-engine.compatibility_extensions',[]) as $name){
            $name=strtolower(trim((string)$name));if($name==='')continue;
            $loaded=extension_loaded($name);$version=$loaded?phpversion($name):false;
            $extensions[$name]=['loaded'=>$loaded,'version'=>$loaded?($version===false?'builtin':(string)$version):null];
        }
        ksort($extensions,SORT_STRING);$pdo=class_exists(\PDO::class)?\PDO::getAvailableDrivers():[];sort($pdo,SORT_STRING);
        $ini=[];foreach((array)config('nexora-engine.compatibility_ini',[]) as $key){$ini[(string)$key]=(string)ini_get((string)$key);}ksort($ini,SORT_STRING);
        $extensionProfile=hash('sha256',json_encode($extensions,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return [
            'php_version'=>PHP_VERSION,
            'php_version_id'=>PHP_VERSION_ID,
            'php_major_minor'=>PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
            'php_int_size'=>PHP_INT_SIZE,
            'zend_version'=>zend_version(),
            'zend_thread_safe'=>defined('ZEND_THREAD_SAFE')?(bool)ZEND_THREAD_SAFE:false,
            'extensions'=>$extensions,
            'extension_profile_sha256'=>$extensionProfile,
            'pdo_drivers'=>$pdo,
            'pdo_drivers_sha256'=>hash('sha256',json_encode($pdo,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),
            'openssl_version'=>defined('OPENSSL_VERSION_TEXT')?(string)OPENSSL_VERSION_TEXT:null,
            'sodium_version'=>defined('SODIUM_LIBRARY_VERSION')?(string)SODIUM_LIBRARY_VERSION:null,
            'icu_version'=>defined('INTL_ICU_VERSION')?(string)INTL_ICU_VERSION:null,
            'compatibility_ini'=>$ini,
        ];
    }

    /** @return array<string,mixed> */
    private function processProfile(): array
    {
        return [
            'sapi'=>PHP_SAPI,
            'binary'=>basename(PHP_BINARY),
            'binary_sha256'=>is_file(PHP_BINARY)?(hash_file('sha256',PHP_BINARY)?:null):null,
            'os_family'=>PHP_OS_FAMILY,
            'memory_limit'=>(string)ini_get('memory_limit'),
            'max_execution_time'=>(string)ini_get('max_execution_time'),
            'opcache_enabled'=>extension_loaded('Zend OPcache')&&filter_var((string)ini_get('opcache.enable'),FILTER_VALIDATE_BOOL),
            'opcache_jit'=>(string)ini_get('opcache.jit'),
        ];
    }

    /** @return list<string> */
    private function missingRequiredExtensions(): array
    {
        $missing=[];foreach((array)config('nexora-engine.required_extensions',[]) as $name)if(!extension_loaded((string)$name))$missing[]=(string)$name;sort($missing,SORT_STRING);return $missing;
    }

    /** @param array<string,mixed> $materials */
    private function fingerprint(array $materials): string
    {
        return hash('sha256',json_encode($this->canonicalize($materials),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value): mixed
    {
        if(!is_array($value)) return $value;
        if(array_is_list($value)) return array_map(fn(mixed $item):mixed=>$this->canonicalize($item),$value);
        ksort($value,SORT_STRING);foreach($value as $key=>$item)$value[$key]=$this->canonicalize($item);return $value;
    }
}
