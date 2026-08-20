<?php

declare(strict_types=1);

return [
    'php' => ['minimum' => '8.3.0', 'maximum_exclusive' => '8.5.0'],
    'composer' => ['minimum' => '2.7.0', 'maximum_exclusive' => '3.0.0'],
    'node' => ['minimum_major' => 22, 'maximum_major_exclusive' => 25],
    'npm' => ['minimum_major' => 10, 'maximum_major_exclusive' => 11],
    'lockfiles' => [
        'composer' => 'composer.lock',
        'npm' => 'package-lock.json',
    ],
    'deterministic_install' => [
        'composer' => ['composer','install','--no-interaction','--prefer-dist','--optimize-autoloader','--no-progress'],
        'npm' => ['npm','ci','--no-audit','--no-fund'],
    ],
    'audit' => [
        'composer' => ['composer','audit','--locked','--no-interaction'],
        'npm' => ['npm','audit','--audit-level=high'],
    ],
    'forbid_unlocked_certification' => true,
    'forbid_install_mutating_lockfiles' => true,
];
