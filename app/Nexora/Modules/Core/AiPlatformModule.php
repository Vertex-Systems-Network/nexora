<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class AiPlatformModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.ai',
            name: 'Nexora AI Platform',
            version: '0.34.0',
            description: 'Provider-neutral tenant AI connections, bounded text generation, privacy-minimal run metadata and extension-backed provider adapters.',
            core: true,
            loadOrder: 67,
            capabilities: [
                'ai.connections.read', 'ai.connections.write', 'ai.generate', 'ai.providers.register', 'admin.navigation.register',
            ],
            dependencies: [
                new ModuleDependency('nexora.admin', '^0.5'),
                new ModuleDependency('nexora.enterprise', '^0.33'),
                new ModuleDependency('nexora.extensions', '^0.29'),
            ],
            metadata: [
                'providers' => 'verified extension registered adapters',
                'prompt_storage' => 'sha256-and-length-only',
                'output_storage' => 'sha256-and-length-only',
                'credentials' => 'encrypted-at-rest',
                'admission' => 'core-enforced input/output/daily request bounds',
                'automatic_provider_retry' => false,
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'ai-platform',
            'label' => 'AI Platform',
            'href' => '/admin/ai',
            'icon' => 'sparkles',
            'order' => 81,
            'permission' => 'ai.view',
        ]);
    }

    public function boot(): void {}
}
