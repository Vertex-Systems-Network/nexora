<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

final readonly class SbomService
{
    public function __construct(private PackageJsonReader $json) {}

    /** @return array{format:string,version:string,bom:array<string,mixed>,components:list<array<string,mixed>>,source:string} */
    public function inspect(string $zipPath, string $artifactSha256): array
    {
        $declared = $this->json->read($zipPath, 'sbom.cdx.json') ?? $this->json->read($zipPath, 'bom.json');
        if (is_array($declared) && ($declared['bomFormat'] ?? null) === 'CycloneDX' && is_array($declared['components'] ?? null)) {
            $components = $this->normalizeDeclared((array) $declared['components']);
            return ['format'=>'CycloneDX','version'=>(string) ($declared['specVersion'] ?? '1.5'),'bom'=>$declared,'components'=>$components,'source'=>'declared'];
        }

        $components = [...$this->composerComponents($zipPath), ...$this->npmComponents($zipPath)];
        $bom = [
            'bomFormat'=>'CycloneDX',
            'specVersion'=>'1.5',
            'version'=>1,
            'metadata'=>['component'=>['type'=>'application','name'=>'Nexora package','hashes'=>[['alg'=>'SHA-256','content'=>$artifactSha256]]]],
            'components'=>array_map(static fn(array $c): array => array_filter([
                'type'=>'library','name'=>$c['name'],'version'=>$c['version'] ?: null,'purl'=>$c['purl'] ?: null,
                'scope'=>$c['scope']==='development' ? 'optional' : 'required',
            ], static fn($v): bool => $v !== null), $components),
        ];
        return ['format'=>'CycloneDX','version'=>'1.5','bom'=>$bom,'components'=>$components,'source'=>'generated'];
    }

    /** @return list<array<string,mixed>> */
    private function composerComponents(string $zipPath): array
    {
        $lock = $this->json->read($zipPath, 'composer.lock', 8_388_608);
        $out = [];
        if (is_array($lock)) {
            foreach ([['packages','runtime'],['packages-dev','development']] as [$key,$scope]) {
                foreach ((array) ($lock[$key] ?? []) as $pkg) {
                    if (! is_array($pkg) || ! is_string($pkg['name'] ?? null)) continue;
                    $name=(string)$pkg['name']; $version=(string)($pkg['version'] ?? '');
                    $out[]=['ecosystem'=>'composer','name'=>$name,'version'=>$version,'scope'=>$scope,'is_direct'=>false,'purl'=>'pkg:composer/'.rawurlencode($name).($version!==''?'@'.rawurlencode($version):''),'licenses'=>(array)($pkg['license']??[]),'hashes'=>[],'metadata'=>[]];
                }
            }
            return $out;
        }
        $manifest = $this->json->read($zipPath, 'composer.json');
        if (! is_array($manifest)) return [];
        foreach ([['require','runtime'],['require-dev','development']] as [$key,$scope]) {
            foreach ((array)($manifest[$key]??[]) as $name=>$version) {
                if ($name==='php' || str_starts_with((string)$name,'ext-')) continue;
                $out[]=['ecosystem'=>'composer','name'=>(string)$name,'version'=>(string)$version,'scope'=>$scope,'is_direct'=>true,'purl'=>'pkg:composer/'.rawurlencode((string)$name),'licenses'=>[],'hashes'=>[],'metadata'=>['constraint'=>(string)$version]];
            }
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function npmComponents(string $zipPath): array
    {
        $lock = $this->json->read($zipPath, 'package-lock.json', 12_582_912);
        $manifest = $this->json->read($zipPath, 'package.json');
        $direct = array_fill_keys(array_merge(array_keys((array)($manifest['dependencies']??[])), array_keys((array)($manifest['devDependencies']??[]))), true);
        $dev = array_fill_keys(array_keys((array)($manifest['devDependencies']??[])), true);
        $out=[];
        if (is_array($lock) && is_array($lock['packages'] ?? null)) {
            foreach ($lock['packages'] as $path=>$pkg) {
                if ($path==='' || ! is_array($pkg)) continue;
                $name=(string)($pkg['name'] ?? preg_replace('~^node_modules/~','',(string)$path));
                if ($name==='') continue;
                $version=(string)($pkg['version']??'');
                $out[]=['ecosystem'=>'npm','name'=>$name,'version'=>$version,'scope'=>(bool)($pkg['dev']??false)?'development':'runtime','is_direct'=>isset($direct[$name]),'purl'=>'pkg:npm/'.rawurlencode($name).($version!==''?'@'.rawurlencode($version):''),'licenses'=>isset($pkg['license'])?[(string)$pkg['license']]:[],'hashes'=>[],'metadata'=>[]];
            }
            return $out;
        }
        if (! is_array($manifest)) return [];
        foreach ($direct as $name=>$_) {
            $constraint=(string)(($manifest['dependencies'][$name]??$manifest['devDependencies'][$name]??''));
            $out[]=['ecosystem'=>'npm','name'=>(string)$name,'version'=>$constraint,'scope'=>isset($dev[$name])?'development':'runtime','is_direct'=>true,'purl'=>'pkg:npm/'.rawurlencode((string)$name),'licenses'=>[],'hashes'=>[],'metadata'=>['constraint'=>$constraint]];
        }
        return $out;
    }

    /** @param list<mixed> $items @return list<array<string,mixed>> */
    private function normalizeDeclared(array $items): array
    {
        $out=[];
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['name']??null)) continue;
            $purl=(string)($item['purl']??'');
            $ecosystem='generic';
            if (str_starts_with($purl,'pkg:composer/')) $ecosystem='composer'; elseif(str_starts_with($purl,'pkg:npm/')) $ecosystem='npm';
            $out[]=['ecosystem'=>$ecosystem,'name'=>(string)$item['name'],'version'=>(string)($item['version']??''),'scope'=>(string)($item['scope']??'runtime'),'is_direct'=>false,'purl'=>$purl,'licenses'=>(array)($item['licenses']??[]),'hashes'=>(array)($item['hashes']??[]),'metadata'=>[]];
        }
        return $out;
    }
}
