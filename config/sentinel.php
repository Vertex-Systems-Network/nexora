<?php

declare(strict_types=1);

return [
    'engine_version' => '0.28.0',

    // Config is intentionally framework-helper independent so Composer package discovery
    // can evaluate configuration during the earliest Laravel bootstrap phases.
    'quarantine_path' => dirname(__DIR__).'/storage/nexora/quarantine',
    'state_path' => dirname(__DIR__).'/storage/nexora/sentinel',

    'archive' => [
        'max_entries' => (int) env('NEXORA_SENTINEL_MAX_ENTRIES', 5000),
        'max_total_uncompressed_bytes' => (int) env('NEXORA_SENTINEL_MAX_TOTAL_BYTES', 262_144_000), // 250 MiB
        'max_entry_uncompressed_bytes' => (int) env('NEXORA_SENTINEL_MAX_ENTRY_BYTES', 52_428_800), // 50 MiB
        'max_source_scan_bytes' => (int) env('NEXORA_SENTINEL_MAX_SOURCE_BYTES', 2_097_152), // 2 MiB per text/source file
        'max_compression_ratio' => (int) env('NEXORA_SENTINEL_MAX_COMPRESSION_RATIO', 200),
    ],

    'upload' => [
        'max_kilobytes' => (int) env('NEXORA_SENTINEL_MAX_UPLOAD_KB', 51_200),
        'extensions' => ['zip'],
    ],

    'package_types' => [
        'module',
        'app',
        'extension',
        'integration',
        'theme',
        'studio-pack',
    ],
];
