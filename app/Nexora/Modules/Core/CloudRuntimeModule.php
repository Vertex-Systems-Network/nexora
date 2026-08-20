<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class CloudRuntimeModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.cloud-runtime',
            name: 'Nexora Cloud Runtime',
            version: '0.34.0',
            description: 'Single-node compatible distributed-runtime foundations for nodes, scheduler leadership, queues, object storage, health, metrics and backup orchestration.',
            core: true,
            loadOrder: 88,
            capabilities: [
                'cloud.topology.read', 'cloud.nodes.manage', 'cloud.leases.manage', 'cloud.storage.read', 'cloud.storage.write',
                'cloud.metrics.read', 'cloud.metrics.write', 'cloud.backups.manage', 'cloud.restore.plan', 'admin.navigation.register',
            ],
            dependencies: [
                new ModuleDependency('nexora.foundation', '^0.5'),
                new ModuleDependency('nexora.admin', '^0.5'),
                new ModuleDependency('nexora.enterprise', '^0.33'),
            ],
            metadata: [
                'scheduler_leadership' => 'database lease',
                'object_storage' => 'Laravel filesystem adapter boundary',
                'restore' => 'checksum-verified offline restore plan; no unattended destructive restore',
                'single_node_compatible' => true,
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id' => 'cloud-runtime',
            'label' => 'Cloud & Operations',
            'href' => '/admin/cloud',
            'icon' => 'cloud',
            'order' => 89,
            'permission' => 'cloud.operations.view',
        ]);
    }

    public function boot(): void {}
}
