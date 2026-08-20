<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final class FoundationModule implements ModuleContract
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.foundation',
            name: 'Nexora Foundation',
            version: '0.5.0',
            description: 'Core settings, audit and platform foundation.',
            core: true,
            loadOrder: 10,
            capabilities: [
                'platform.settings.read',
                'platform.settings.write',
                'system.audit.write',
                'system.health.read',
            ],
        );
    }

    public function register(): void
    {
        // Foundation services are container bindings owned by NexoraServiceProvider.
    }

    public function boot(): void
    {
    }
}
