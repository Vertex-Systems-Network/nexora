<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class DocumentEngineModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.documents',
            name: 'Nexora Document Engine',
            version: '0.19.0',
            description: 'Universal structured-document foundation with typed blocks, revisions, tenant-aware content collections and reusable publishing contracts.',
            core: true,
            loadOrder: 35,
            capabilities: ['content.documents.read', 'content.documents.write', 'content.revisions.read', 'content.revisions.write', 'content.editorial.review', 'content.autosave.write', 'admin.navigation.register'],
            dependencies: [
                new ModuleDependency('nexora.foundation', '^0.5'),
                new ModuleDependency('nexora.identity-access', '^0.5'),
                new ModuleDependency('nexora.admin', '^0.5'),
            ],
            metadata: ['schema_version' => 2, 'storage' => 'structured-json'],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'documents',
            'label' => 'Documents',
            'href' => '/admin/documents',
            'icon' => 'file-text',
            'order' => 55,
            'permission' => 'documents.view',
        ]);
        $this->navigation->register([
            'id' => 'collections',
            'label' => 'Collections',
            'href' => '/admin/collections',
            'icon' => 'layers',
            'order' => 56,
            'permission' => 'collections.view',
        ]);
    }

    public function boot(): void
    {
        require base_path('routes/content-collections.php');
    }
}
