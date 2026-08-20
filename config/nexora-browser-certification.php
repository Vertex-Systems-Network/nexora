<?php

declare(strict_types=1);

return [
    'browsers' => ['chrome', 'edge', 'firefox'],
    'viewports' => [
        ['name' => 'mobile', 'width' => 360],
        ['name' => 'tablet', 'width' => 768],
        ['name' => 'desktop', 'width' => 1440],
    ],
    'directions' => ['ltr', 'rtl'],
    'themes' => ['light', 'dark'],
    'checks' => [
        'keyboard_navigation',
        'focus_visible',
        'skip_link',
        'modal_focus_trap',
        'command_palette_keyboard',
        'screen_reader_labels',
        'reduced_motion',
        'zoom_200',
        'forced_colors',
        'no_page_horizontal_overflow',
    ],
    'web_vitals' => [
        'routes' => ['/', '/login'],
        'thresholds' => [
            'lcp_ms' => 2500,
            'inp_ms' => 200,
            'cls' => 0.10,
            'ttfb_ms' => 800,
        ],
        'minimum_runs_per_route' => 3,
    ],
];
