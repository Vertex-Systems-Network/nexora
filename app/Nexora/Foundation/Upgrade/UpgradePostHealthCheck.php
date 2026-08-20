<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Installation\InstallationState;
use Illuminate\Support\Facades\DB;

final readonly class UpgradePostHealthCheck
{
    public function __construct(private InstallationState $installation) {}

    /** @return array{ok:bool,phase:string,checks:array<string,bool>,health_sha256:string} */
    public function verify(string $phase,string $expectedInstalledVersion,string $targetVersion): array
    {
        if(!in_array($phase,['pre_metadata_commit','post_metadata_commit'],true)) throw new \InvalidArgumentException('Unknown upgrade health-check phase.');
        $database=false;
        try { DB::select('select 1 as nexora_upgrade_health'); $database=true; } catch(\Throwable) { $database=false; }
        $routes=false;
        try { $routes=app('router')->getRoutes()->count()>=(int)config('nexora-upgrade.post_health_min_routes',1); } catch(\Throwable) { $routes=false; }
        $storage=is_dir(storage_path('app'))&&is_writable(storage_path('app'));
        $bootstrap=is_dir(base_path('bootstrap/cache'))&&is_writable(base_path('bootstrap/cache'));
        $metadata=$this->installation->metadata();
        $installed=is_array($metadata)&&(string)($metadata['version']??'')===$expectedInstalledVersion;
        $target=(string)config('nexora.version','')===$targetVersion;
        $checks=[
            'database_ping'=>$database,
            'route_registry'=>$routes,
            'storage_writable'=>$storage,
            'bootstrap_cache_writable'=>$bootstrap,
            'installation_version'=>$installed,
            'target_source_version'=>$target,
        ];
        $canonical=['phase'=>$phase,'checks'=>$checks,'expected_installed_version'=>$expectedInstalledVersion,'target_version'=>$targetVersion];
        return ['ok'=>!in_array(false,$checks,true),'phase'=>$phase,'checks'=>$checks,'health_sha256'=>hash('sha256',json_encode($canonical,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))];
    }

    /** @return array{ok:bool,phase:string,checks:array<string,bool>,health_sha256:string} */
    public function assertHealthy(string $phase,string $expectedInstalledVersion,string $targetVersion): array
    {
        $result=$this->verify($phase,$expectedInstalledVersion,$targetVersion);
        if(!$result['ok']) {
            $failed=array_keys(array_filter($result['checks'],static fn(bool $ok): bool => !$ok));
            throw new \RuntimeException('Post-upgrade health gate failed ['.implode(', ',$failed).']. Traffic remains protected.');
        }
        return $result;
    }
}
