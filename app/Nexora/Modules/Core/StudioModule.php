<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class StudioModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.studio',
            name: 'Nexora Studio',
            version: '0.21.0',
            description: 'Visual canvas foundation with typed elements, responsive styles, safe data bindings, revisions and reusable components.',
            core: true,
            loadOrder: 44,
            capabilities: [
                'studio.canvas.read', 'studio.canvas.write', 'studio.components.read', 'studio.components.write',
                'studio.bindings.read', 'content.documents.read', 'themes.registry.read', 'admin.navigation.register',
            ],
            dependencies: [
                new ModuleDependency('nexora.admin', '^0.5'),
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.themes', '^0.20'),
            ],
            metadata: ['schema_version' => 1, 'responsive_breakpoints' => ['desktop', 'tablet', 'mobile'], 'safe_bindings' => true],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'studio', 'label' => 'Studio', 'href' => '/admin/studio', 'icon' => 'studio', 'order' => 57, 'permission' => 'studio.view',
        ]);
    }

    public function boot(): void {}
}
