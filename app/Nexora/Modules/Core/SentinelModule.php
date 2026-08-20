<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class SentinelModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.sentinel',
            name: 'Nexora Sentinel',
            version: '0.5.0',
            description: 'Zero-trust package quarantine, static security scanning and risk decision engine.',
            core: true,
            loadOrder: 50,
            capabilities: [
                'admin.navigation.register',
                'security.sentinel.scan',
                'security.findings.read',
                'security.quarantine.manage',
                'system.audit.write',
            ],
            dependencies: [
                new ModuleDependency('nexora.foundation', '^0.5'),
                new ModuleDependency('nexora.admin', '^0.5'),
                new ModuleDependency('nexora.runtime', '^0.5'),
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'sentinel',
            'label' => 'Sentinel',
            'href' => '/admin/security/sentinel',
            'icon' => 'sentinel',
            'order' => 45,
            'permission' => 'security.sentinel.view',
        ]);
    }

    public function boot(): void
    {
    }
}
