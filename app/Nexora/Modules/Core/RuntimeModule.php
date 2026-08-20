<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class RuntimeModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.runtime',
            name: 'Nexora Runtime',
            version: '0.5.0',
            description: 'Module registry, capability catalog and runtime diagnostics.',
            core: true,
            loadOrder: 40,
            capabilities: [
                'admin.navigation.register',
                'system.modules.read',
                'system.capabilities.read',
                'system.runtime.sync',
            ],
            dependencies: [
                new ModuleDependency('nexora.foundation', '^0.5'),
                new ModuleDependency('nexora.admin', '^0.5'),
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'system-modules', 'label' => 'Modules', 'href' => '/admin/system/modules', 'icon' => 'blocks', 'order' => 91, 'permission' => 'system.modules.view',
        ]);
        $this->navigation->register([
            'id' => 'system-capabilities', 'label' => 'Capabilities', 'href' => '/admin/system/capabilities', 'icon' => 'key', 'order' => 92, 'permission' => 'system.capabilities.view',
        ]);
    }

    public function boot(): void
    {
    }
}
