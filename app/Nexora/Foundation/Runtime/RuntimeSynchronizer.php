<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Models\Capability;
use App\Models\Module;
use App\Models\ModuleDependency as ModuleDependencyModel;
use App\Models\ModuleVersion;
use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Illuminate\Support\Facades\DB;

final readonly class RuntimeSynchronizer
{
    public function __construct(
        private ModuleRegistryContract $modules,
        private CapabilityRegistryContract $capabilities,
    ) {
    }

    /** @return array{modules:int,capabilities:int} */
    public function sync(): array
    {
        return DB::transaction(function (): array {
            foreach ($this->capabilities->all() as $definition) {
                Capability::query()->updateOrCreate(
                    ['slug' => $definition->slug],
                    [
                        'name' => $definition->name,
                        'group' => $definition->group,
                        'risk_level' => $definition->risk->value,
                        'description' => $definition->description,
                    ],
                );
            }

            foreach ($this->modules->manifests() as $identifier => $manifest) {
                $class = $this->modules->classes()[$identifier];
                $module = Module::query()->updateOrCreate(
                    ['identifier' => $identifier],
                    [
                        'name' => $manifest->name,
                        'class' => $class,
                        'version' => $manifest->version,
                        'status' => 'active',
                        'load_order' => $manifest->loadOrder,
                        'trust_level' => $manifest->core ? 'core' : 'trusted',
                        'manifest_hash' => $manifest->hash(),
                        'last_booted_at' => now(),
                        'is_core' => $manifest->core,
                        'metadata' => array_merge($manifest->metadata, ['description' => $manifest->description]),
                    ],
                );

                if ($module->enabled_at === null) {
                    $module->forceFill(['enabled_at' => now()])->save();
                }

                $version = ModuleVersion::query()->firstOrCreate(
                    ['module_id' => $module->id, 'version' => $manifest->version],
                    ['checksum' => $manifest->hash(), 'installed_at' => now(), 'metadata' => ['class' => $class]],
                );

                if (! $version->wasRecentlyCreated && $version->checksum !== null && ! hash_equals($version->checksum, $manifest->hash())) {
                    $version->forceFill([
                        'metadata' => array_merge((array) $version->metadata, [
                            'class' => $class,
                            'last_observed_checksum' => $manifest->hash(),
                            'integrity_mismatch_at' => now()->toIso8601String(),
                        ]),
                    ])->save();
                }

                ModuleDependencyModel::query()->where('module_id', $module->id)->delete();
                foreach ($manifest->dependencies as $dependency) {
                    ModuleDependencyModel::query()->create([
                        'module_id' => $module->id,
                        'dependency_identifier' => $dependency->identifier,
                        'version_constraint' => $dependency->constraint,
                        'is_optional' => $dependency->optional,
                    ]);
                }

                $capabilityIds = Capability::query()->whereIn('slug', $manifest->capabilities)->pluck('id');
                $sync = [];
                foreach ($capabilityIds as $capabilityId) {
                    $sync[(int) $capabilityId] = ['mode' => 'requested'];
                }
                $module->capabilities()->sync($sync);
            }

            return [
                'modules' => count($this->modules->manifests()),
                'capabilities' => count($this->capabilities->all()),
            ];
        });
    }
}
