<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class AdminModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation)
    {
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.admin',
            name: 'Nexora Admin',
            version: '0.5.0',
            description: 'Premium administration shell and shared interaction system.',
            core: true,
            loadOrder: 30,
            capabilities: [
                'admin.navigation.register',
                'admin.notifications.read',
                'admin.search.use',
                'system.audit.write',
            ],
            dependencies: [
                new ModuleDependency('nexora.foundation', '^0.5'),
                new ModuleDependency('nexora.identity-access', '^0.5'),
            ],
        );
    }

    public function register(): void
    {
        $items = [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/admin', 'icon' => 'home', 'order' => 10, 'permission' => 'admin.access'],
            ['id' => 'users', 'label' => 'Users', 'href' => '/admin/users', 'icon' => 'users', 'order' => 20, 'permission' => 'users.view'],
            ['id' => 'roles', 'label' => 'Roles & Access', 'href' => '/admin/roles', 'icon' => 'shield', 'order' => 30, 'permission' => 'roles.view'],
            ['id' => 'audit', 'label' => 'Audit Trail', 'href' => '/admin/audit', 'icon' => 'history', 'order' => 40, 'permission' => 'audit.view'],
            ['id' => 'notifications', 'label' => 'Notifications', 'href' => '/admin/notifications', 'icon' => 'bell', 'order' => 50, 'permission' => 'notifications.view'],
            ['id' => 'data-connections', 'label' => 'Data Connections', 'href' => '/admin/data/connections', 'icon' => 'database', 'order' => 60, 'permission' => 'data.connections.view'],
            ['id' => 'settings', 'label' => 'Settings', 'href' => '/admin/settings', 'icon' => 'settings', 'order' => 80, 'permission' => 'settings.manage'],
            ['id' => 'system-health', 'label' => 'System Health', 'href' => '/admin/system/health', 'icon' => 'activity', 'order' => 90, 'permission' => 'system.health.view'],
        ];

        foreach ($items as $item) {
            $this->navigation->register($item);
        }
    }

    public function boot(): void
    {
    }
}
