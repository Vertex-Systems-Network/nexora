<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Inertia\Inertia;
use Inertia\Response;

final class CapabilityRuntimeController extends Controller
{
    public function __invoke(CapabilityRegistryContract $capabilities, ModuleRegistryContract $modules): Response
    {
        $requestedBy = [];
        foreach ($modules->manifests() as $manifest) {
            foreach ($manifest->capabilities as $slug) {
                $requestedBy[$slug][] = $manifest->identifier;
            }
        }

        $items = collect($capabilities->all())->map(static fn ($capability): array => [
            'id' => $capability->slug,
            ...$capability->toArray(),
            'requestedBy' => $requestedBy[$capability->slug] ?? [],
        ])->values();

        return Inertia::render('Admin/System/Capabilities', [
            'capabilities' => $items,
            'summary' => [
                'total' => $items->count(),
                'critical' => $items->where('risk_level', 'critical')->count(),
                'sensitive' => $items->where('risk_level', 'sensitive')->count(),
            ],
        ]);
    }
}
