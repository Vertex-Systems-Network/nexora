<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class SeoModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.seo',
            name: 'Nexora SEO Core',
            version: '0.19.0',
            description: 'Theme-independent SEO metadata, central Schema Graph, canonical/indexing policy, sitemap generation and internal-link analysis.',
            core: true,
            loadOrder: 39,
            capabilities: ['seo.metadata.read', 'seo.metadata.write', 'seo.schema.read', 'seo.schema.write', 'seo.sitemap.read', 'seo.links.analyze', 'admin.navigation.register'],
            dependencies: [
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.admin', '^0.5'),
            ],
            metadata: ['json_ld' => true, 'sitemap' => true, 'canonical' => true, 'robots' => true, 'internal_links' => true],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'seo',
            'label' => 'SEO & Discovery',
            'href' => '/admin/seo',
            'icon' => 'search',
            'order' => 58,
            'permission' => 'seo.view',
        ]);
    }

    public function boot(): void {}
}
