<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final class IdentityAccessModule implements ModuleContract
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.identity-access',
            name: 'Nexora Identity & Access',
            version: '0.5.0',
            description: 'Users, roles, permissions and authenticated sessions.',
            core: true,
            loadOrder: 20,
            capabilities: [
                'identity.users.read',
                'identity.users.write',
                'identity.roles.read',
                'identity.roles.write',
                'identity.sessions.manage',
                'system.audit.write',
            ],
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
