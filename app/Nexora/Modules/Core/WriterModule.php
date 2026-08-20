<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class WriterModule implements ModuleContract
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.writer',
            name: 'Nexora Writer',
            version: '0.18.0',
            description: 'Semantic block editing, writer insights and publishing-oriented document authoring foundations.',
            core: true,
            loadOrder: 36,
            capabilities: ['content.writer.use', 'content.documents.read', 'content.documents.write', 'content.revisions.read', 'content.revisions.write', 'content.editorial.review', 'content.autosave.write'],
            dependencies: [
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.admin', '^0.5'),
            ],
            metadata: ['editor' => 'structured-blocks', 'autosave' => true, 'editorial_workflow' => true, 'revision_compare_restore' => true, 'collaboration' => false],
        );
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
