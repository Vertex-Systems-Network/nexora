<?php

declare(strict_types=1);

return [
    'laravel' => [
        // Laravel 13 follows SemVer for minor / patch releases. Keep the major
        // boundary explicit so future 13.x updates are accepted without silently
        // opting into a breaking Laravel 14 upgrade.
        'minimum' => '13.24.0',
        'maximum_exclusive' => '14.0.0',
        'composer_constraint' => '^13.24',
    ],


    'fresh_install_dependency_trust' => [
        // A clean source archive deliberately excludes operator review evidence.
        // Fresh installation may bootstrap trust only when deterministic locks
        // exist and the running Laravel version exactly matches composer.lock.
        'enabled' => true,
        'require_installed_composer_match' => true,
        'require_npm_manifest_lock_match' => true,
        'receipt_path' => storage_path('app/nexora/dependency-intake/fresh-install-bootstrap.json'),
    ],

    'dependency_reconciliation' => [
        'enabled' => true,
        'require_maintenance_mode' => true,
        'require_reviewed_locks' => true,
        'require_same_source_tree' => true,
        'require_same_frontend_manifest' => true,
        'require_same_session_schema' => true,
        'receipt_path' => storage_path('app/nexora/dependency-intake/runtime-dependency-transition.json'),
    ],
];
