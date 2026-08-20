<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final class InstallationModule implements ModuleContract
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.installation',
            name: 'Nexora Installation',
            version: '0.18.0',
            description: 'Pre-Laravel runtime repair, deployment bootstrap, release readiness, portable database/environment provisioning, first-admin creation and installer lock lifecycle.',
            core: true,
            loadOrder: 15,
            capabilities: [],
            dependencies: [new ModuleDependency('nexora.foundation', '^0.5')],
        );
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
