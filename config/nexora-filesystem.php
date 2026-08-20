<?php

declare(strict_types=1);

return [
    'repository_max_relative_path' => 200,
    'required_writable_directories' => [
        base_path('bootstrap/cache'),
        storage_path('app'),
        storage_path('app/public'),
        storage_path('app/nexora'),
        storage_path('app/nexora/transfers'),
        storage_path('framework/cache/data'),
        storage_path('framework/sessions'),
        storage_path('framework/views'),
        storage_path('logs'),
        storage_path('nexora/cache'),
        storage_path('nexora/logs'),
        storage_path('nexora/packages'),
        storage_path('nexora/quarantine'),
        storage_path('nexora/sentinel'),
    ],
    'protected_local_directories' => [
        storage_path('app/nexora/environment'),
        storage_path('app/nexora/database-backups'),
        storage_path('app/nexora/upgrade'),
        storage_path('app/nexora/transfers'),
        storage_path('nexora/quarantine'),
    ],
    'atomic_state_files' => [
        (string) config('installer.lock_path', storage_path('app/nexora/installed.lock')),
        (string) config('installer.environment_marker_path', storage_path('app/nexora/environment/active')),
        storage_path('app/nexora/runtime/node-id'),
        (string) config('nexora-upgrade.plan_path', storage_path('app/nexora/upgrade/active-plan.json')),
    ],
];
