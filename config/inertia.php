<?php

declare(strict_types=1);

return [
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [
            resource_path('js/admin/pages'),
        ],
        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],
];
