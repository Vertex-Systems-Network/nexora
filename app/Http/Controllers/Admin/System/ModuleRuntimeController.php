<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ModuleRuntimeController extends Controller
{
    public function __invoke(Request $request, ModuleRegistryContract $registry): Response
    {
        $stored = Module::query()
            ->with(['dependencies:id,module_id,dependency_identifier,version_constraint,is_optional', 'capabilities:id,slug,name,risk_level', 'versions:id,module_id,version,checksum,installed_at'])
            ->get()
            ->keyBy('identifier');
        $bootOrder = array_flip($registry->bootOrder());

        $modules = collect($registry->manifests())->map(function ($manifest, string $identifier) use ($stored, $registry, $bootOrder): array {
            $record = $stored->get($identifier);
            $versionRecord = $record?->versions?->firstWhere('version', $manifest->version);
            $manifestHash = $manifest->hash();

            return [
                'id' => $identifier,
                'identifier' => $identifier,
                'name' => $manifest->name,
                'version' => $manifest->version,
                'description' => $manifest->description,
                'class' => $registry->classes()[$identifier],
                'core' => $manifest->core,
                'loadOrder' => $manifest->loadOrder,
                'bootPosition' => ($bootOrder[$identifier] ?? 0) + 1,
                'capabilities' => $manifest->capabilities,
                'dependencies' => array_map(static fn ($dependency): array => $dependency->toArray(), $manifest->dependencies),
                'manifestHash' => $manifestHash,
                'synced' => $record !== null && hash_equals((string) $record->manifest_hash, $manifestHash),
                'versionIntegrity' => $versionRecord === null ? null : ($versionRecord->checksum === null || hash_equals((string) $versionRecord->checksum, $manifestHash)),
                'lastBootedAt' => $record?->last_booted_at?->toIso8601String(),
            ];
        })->values();

        return Inertia::render('Admin/System/Modules', [
            'modules' => $modules,
            'summary' => [
                'registered' => $modules->count(),
                'core' => $modules->where('core', true)->count(),
                'synced' => $modules->where('synced', true)->count(),
                'integrityIssues' => $modules->where('versionIntegrity', false)->count(),
            ],
            'canSync' => $request->user()?->hasPermission('system.runtime.sync') ?? false,
        ]);
    }
}
