<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\Capability;
use App\Models\Extension;
use App\Models\ExtensionCapabilityGrant;
use App\Models\ExtensionLifecycleEvent;
use App\Models\ExtensionVersion;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use App\Nexora\Security\SupplyChain\Contracts\SandboxAdapterContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class ExtensionLifecycleManager
{
    public function __construct(private VersionConstraintMatcher $versions, private SandboxAdapterContract $sandbox, private ExtensionMigrationRunner $migrations) {}

    public function grantCapabilities(Extension $extension, array $slugs, ?int $actorId): void
    {
        $version=$this->currentOrLatest($extension); $requested=array_values(array_unique(array_filter((array) ($version->manifest['capabilities'] ?? []),'is_string')));
        $known=Capability::query()->whereIn('slug',$slugs)->pluck('slug')->all();
        foreach ($requested as $slug) {
            $granted=in_array($slug,$known,true);
            ExtensionCapabilityGrant::query()->updateOrCreate(['extension_id'=>$extension->id,'capability_slug'=>$slug],[
                'granted'=>$granted,'granted_by'=>$actorId,'granted_at'=>$granted?now():null,'revoked_at'=>$granted?null:now(),
            ]);
        }
        $this->event($extension,$version,'capabilities.updated',$actorId,['granted'=>$known]);
    }

    public function enable(Extension $extension, ?int $actorId, ?string $versionNumber=null): void
    {
        $version=$versionNumber ? $extension->versions()->where('version',$versionNumber)->firstOrFail() : $this->currentOrLatest($extension);
        $this->assertDependencies($version);
        $requested=array_values(array_unique(array_filter((array) ($version->manifest['capabilities'] ?? []),'is_string')));
        $registered=Capability::query()->whereIn('slug',$requested)->pluck('slug')->all();
        $unregistered=array_values(array_diff($requested,$registered));
        if ($unregistered!==[]) throw new RuntimeException('This extension requests capabilities that are not registered by the current Nexora runtime: '.implode(', ',$unregistered));
        $granted=$extension->grants()->where('granted',true)->pluck('capability_slug')->all();
        $missing=array_values(array_diff($requested,$granted)); if ($missing!==[]) throw new RuntimeException('Grant all requested capabilities before enabling this extension: '.implode(', ',$missing));
        if ($version->runtime_mode==='trusted-php') {
            $artifact=$version->artifact; if (! $artifact) throw new RuntimeException('Trusted PHP runtime requires a verified supply-chain artifact.');
            $policy=$this->sandbox->evaluate($artifact); if (! $policy['execution_allowed']) throw new RuntimeException('Supply-chain execution policy blocked this extension: '.$policy['reason']);
        }
        $this->migrations->apply($version);
        if ($extension->current_version && $extension->current_version!==$version->version) {
            $current=$extension->versions()->where('version',$extension->current_version)->first(); if ($current) $current->forceFill(['state'=>'superseded'])->save();
        }
        $version->forceFill(['state'=>'active','activated_at'=>now()])->save();
        $extension->forceFill(['status'=>'enabled','current_version'=>$version->version,'enabled_at'=>now(),'disabled_at'=>null,'uninstalled_at'=>null])->save();
        $this->event($extension,$version,'enabled',$actorId,['runtime_mode'=>$version->runtime_mode]);
    }

    public function disable(Extension $extension, ?int $actorId): void
    {
        if ($extension->status!=='enabled') return;
        $extension->forceFill(['status'=>'disabled','disabled_at'=>now()])->save();
        $this->event($extension,$this->currentOrLatest($extension),'disabled',$actorId,[]);
    }

    public function rollback(Extension $extension, ?int $actorId): void
    {
        $current=$this->currentOrLatest($extension);
        $target=$extension->versions()->where('version','!=',$current->version)->orderByDesc('installed_at')->first();
        if (! $target) throw new RuntimeException('No previous installed extension version is available for rollback.');
        if ($current->migration_policy!=='none' && ! $current->schema_compatible_rollback) throw new RuntimeException('Rollback is blocked because the active version declares forward-only schema changes without schema-compatible rollback.');
        $this->enable($extension,$actorId,$target->version);
        $this->event($extension,$target,'rolled-back',$actorId,['from'=>$current->version,'to'=>$target->version]);
    }

    public function uninstall(Extension $extension, ?int $actorId): void
    {
        $dependents=ExtensionVersion::query()->whereHas('dependencies',fn($q)=>$q->where('dependency_identifier',$extension->identifier))->whereHas('extension',fn($q)=>$q->where('status','enabled'))->count();
        if ($dependents>0) throw new RuntimeException('Enabled extensions depend on this package. Disable or remove dependents first.');
        foreach ($extension->versions as $version) if (is_dir($version->install_path)) File::deleteDirectory($version->install_path);
        $extension->forceFill(['status'=>'uninstalled','uninstalled_at'=>now(),'enabled_at'=>null])->save();
        $this->event($extension,null,'uninstalled',$actorId,[]);
    }

    private function assertDependencies(ExtensionVersion $version): void
    {
        foreach ($version->dependencies as $dependency) {
            $installed=Extension::query()->where('identifier',$dependency->dependency_identifier)->where('status','enabled')->first();
            if (! $installed) { if ($dependency->optional) continue; throw new RuntimeException("Missing enabled extension dependency [{$dependency->dependency_identifier}]."); }
            if (! $installed->current_version || ! $this->versions->matches($installed->current_version,$dependency->version_constraint)) throw new RuntimeException("Extension dependency [{$dependency->dependency_identifier}] requires {$dependency->version_constraint}; active {$installed->current_version}.");
        }
    }

    private function currentOrLatest(Extension $extension): ExtensionVersion
    {
        if ($extension->current_version) { $current=$extension->versions()->where('version',$extension->current_version)->first(); if ($current) return $current; }
        $latest=$extension->versions()->orderByDesc('installed_at')->first(); if (! $latest) throw new RuntimeException('Extension has no installed version.'); return $latest;
    }

    private function event(Extension $extension, ?ExtensionVersion $version, string $event, ?int $actorId, array $context): void
    {
        ExtensionLifecycleEvent::query()->create(['id'=>(string) Str::uuid(),'extension_id'=>$extension->id,'extension_version_id'=>$version?->id,'event'=>$event,'status'=>'completed','context'=>$context,'actor_id'=>$actorId,'created_at'=>now()]);
    }
}
