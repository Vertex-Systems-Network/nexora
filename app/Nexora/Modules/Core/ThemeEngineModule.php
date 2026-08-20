<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class ThemeEngineModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.themes',
            name: 'Nexora Theme Engine',
            version: '0.20.0',
            description: 'Sentinel-gated non-executable theme packages, safe preview/activation/rollback, design tokens and public template resolution.',
            core: true,
            loadOrder: 42,
            capabilities: [
                'themes.registry.read', 'themes.registry.write', 'themes.preview.create', 'themes.render.public',
                'seo.metadata.read', 'seo.schema.read', 'admin.navigation.register', 'security.sentinel.scan',
            ],
            dependencies: [
                new ModuleDependency('nexora.admin', '^0.5'),
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.seo', '^0.19'),
                new ModuleDependency('nexora.sentinel', '^0.5'),
            ],
            metadata: [
                'engine' => 'nexora-safe-html',
                'sentinel_gate' => true,
                'preview_tokens' => true,
                'rollback' => true,
                'design_tokens' => true,
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'themes',
            'label' => 'Themes',
            'href' => '/admin/appearance/themes',
            'icon' => 'palette',
            'order' => 56,
            'permission' => 'themes.view',
        ]);
    }

    public function boot(): void {}
}
