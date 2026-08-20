<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Models\Extension;
use App\Models\Theme;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use App\Nexora\Foundation\Environment\EnvironmentDoctor;
use App\Nexora\Installation\InstallationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class UpgradeCompatibilityService
{
    public function __construct(private InstallationState $installation, private VersionConstraintMatcher $versions, private EnvironmentDoctor $environment) {}

    /** @return array<string,mixed> */
    public function assess(): array
    {
        $metadata=$this->installation->metadata() ?? [];
        $source=trim((string)($metadata['version']??''));
        $target=trim((string)config('nexora.version',''));
        $supported=(string)config('nexora-upgrade.supported_source','>=0.34 <2.0');
        $errors=[]; $warnings=[];

        if(! $this->installation->isInstalled()) $errors[]='Nexora is not marked installed; use the installer rather than the upgrade workflow.';
        if($source==='' || preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/',$source)!==1) $errors[]='Installed lock does not contain a valid source platform version.';
        if($target==='' || preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/',$target)!==1) $errors[]='Current source tree has an invalid target platform version.';
        if($source!=='' && $target!=='' && version_compare($source,$target,'>')) $errors[]="Downgrade is blocked: installed {$source} is newer than source tree {$target}.";
        if($source!=='' && ! $this->versions->matches($source,$supported)) $errors[]="Installed {$source} is outside supported in-place upgrade range {$supported}.";
        if($source===$target && $source!=='') $warnings[]='Installed lock already reports the current platform version; no platform-version transition is pending.';

        $environment=$this->environment->inspect(true);
        foreach($environment['errors'] as $item) $errors[]='Environment/configuration: '.$item;
        foreach($environment['warnings'] as $item) $warnings[]='Environment/configuration: '.$item;

        $pending=$this->pendingMigrations();
        $extensions=$this->extensionCompatibility($target);
        $themes=$this->themeCompatibility($target);
        foreach($extensions['incompatible'] as $item) $errors[]="Enabled extension [{$item['identifier']}] requires Nexora {$item['constraint']}.";
        foreach($themes['incompatible'] as $item) $errors[]="Active theme [{$item['identifier']}] requires Nexora {$item['constraint']}.";
        foreach($extensions['rollback_barriers'] as $item) $warnings[]="Extension [{$item['identifier']}] has forward-only migrations without schema-compatible rollback; core rollback must use backup/restore.";

        $assessment=[
            'status'=>$errors===[]?'pass':'fail',
            'source_version'=>$source!==''?$source:null,
            'target_version'=>$target,
            'supported_source_constraint'=>$supported,
            'pending_migrations'=>$pending,
            'environment'=>$environment,
            'extensions'=>$extensions,
            'themes'=>$themes,
            'errors'=>$errors,
            'warnings'=>$warnings,
        ];
        $assessment['assessment_sha256']=hash('sha256',json_encode($this->canonicalize($assessment),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $assessment;
    }

    private function canonicalize(mixed $value): mixed
    {
        if(!is_array($value))return $value;
        if(array_is_list($value))return array_map(fn(mixed $item):mixed=>$this->canonicalize($item),$value);
        ksort($value);foreach($value as $key=>$item)$value[$key]=$this->canonicalize($item);return $value;
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        if(! Schema::hasTable('migrations')) return array_map(static fn(string $p): string=>pathinfo($p,PATHINFO_FILENAME),glob(database_path('migrations/*.php'))?:[]);
        $ran=DB::table('migrations')->pluck('migration')->map(static fn($v)=>(string)$v)->all();
        $files=array_map(static fn(string $p): string=>pathinfo($p,PATHINFO_FILENAME),glob(database_path('migrations/*.php'))?:[]);
        sort($files); return array_values(array_diff($files,$ran));
    }

    /** @return array{checked:int,incompatible:list<array{identifier:string,constraint:string}>,rollback_barriers:list<array{identifier:string,version:string}>} */
    private function extensionCompatibility(string $target): array
    {
        $incompatible=[]; $barriers=[]; $checked=0;
        if(! Schema::hasTable('nx_extensions') || ! Schema::hasTable('nx_extension_versions')) return ['checked'=>0,'incompatible'=>[],'rollback_barriers'=>[]];
        foreach(Extension::query()->where('status','enabled')->with('versions')->get() as $extension){
            $current=$extension->current_version ? $extension->versions->firstWhere('version',$extension->current_version) : $extension->versions->sortByDesc('installed_at')->first();
            if(! $current) continue; $checked++;
            $constraint=trim((string)($current->manifest['requires']['nexora']??'*')) ?: '*';
            if(! $this->versions->matches($target,$constraint)) $incompatible[]=['identifier'=>(string)$extension->identifier,'constraint'=>$constraint];
            if($current->migration_policy==='forward-only' && ! $current->schema_compatible_rollback) $barriers[]=['identifier'=>(string)$extension->identifier,'version'=>(string)$current->version];
        }
        return ['checked'=>$checked,'incompatible'=>$incompatible,'rollback_barriers'=>$barriers];
    }

    /** @return array{checked:int,incompatible:list<array{identifier:string,constraint:string}>} */
    private function themeCompatibility(string $target): array
    {
        $incompatible=[]; $checked=0;
        if(! Schema::hasTable('nx_themes') || ! Schema::hasTable('nx_theme_versions')) return ['checked'=>0,'incompatible'=>[]];
        foreach(Theme::query()->where('status','active')->with('currentVersion')->get() as $theme){
            $version=$theme->currentVersion; if(! $version) continue; $checked++;
            $manifest=is_array($version->manifest)?$version->manifest:[];
            $constraint=trim((string)($manifest['requires']['nexora']??'*')) ?: '*';
            if(! $this->versions->matches($target,$constraint)) $incompatible[]=['identifier'=>(string)$theme->identifier,'constraint'=>$constraint];
        }
        return ['checked'=>$checked,'incompatible'=>$incompatible];
    }
}
