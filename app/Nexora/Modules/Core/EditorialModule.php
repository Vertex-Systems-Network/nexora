<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class EditorialModule implements ModuleContract
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.editorial',
            name: 'Nexora Editorial',
            version: '0.18.0',
            description: 'Editorial workflow states, review ownership, conflict-safe autosave, immutable revision comparison and restore.',
            core: true,
            loadOrder: 37,
            capabilities: ['content.editorial.review', 'content.autosave.write', 'content.revisions.read', 'content.revisions.write'],
            dependencies: [
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.writer', '^0.18'),
            ],
            metadata: ['workflow_states' => true, 'review_comments' => true, 'revision_compare' => true, 'revision_restore' => true, 'autosave_conflict_guard' => true],
        );
    }

    public function register(): void {}
    public function boot(): void {}
}
