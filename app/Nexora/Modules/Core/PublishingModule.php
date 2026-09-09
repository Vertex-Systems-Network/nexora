<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;
use App\Nexora\Publishing\Services\ArticlePublishingManager;

final readonly class PublishingModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation, private ArticlePublishingManager $publishing) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.publishing',
            name: 'Nexora Blog & Article Publishing',
            version: '0.22.0',
            description: 'First-party article and blog publishing built on Documents, Editorial, SEO, Themes and Studio.',
            core: true,
            loadOrder: 48,
            capabilities: ['publishing.articles.read', 'publishing.articles.write', 'publishing.taxonomy.manage', 'publishing.authors.manage', 'publishing.series.manage', 'admin.navigation.register'],
            dependencies: [
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.editorial', '^0.18'),
                new ModuleDependency('nexora.seo', '^0.19'),
                new ModuleDependency('nexora.themes', '^0.20'),
            ],
            metadata: ['document_types' => ['article', 'blog_post'], 'taxonomies' => ['category', 'topic', 'tag'], 'scheduling' => true],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'publishing', 'label' => 'Blog & Articles', 'href' => '/admin/publishing/articles',
            'icon' => 'newspaper', 'order' => 57, 'permission' => 'publishing.view',
        ]);
    }

    public function boot(): void
    {
        $this->publishing->registerDocumentLifecycle();
    }
}
