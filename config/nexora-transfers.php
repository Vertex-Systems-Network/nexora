<?php

declare(strict_types=1);

return [
    'temporary_root' => storage_path('app/nexora/transfers'),
    'stream_chunk_bytes' => 1_048_576,
    'minimum_free_bytes' => 67_108_864, // 64 MiB safety reserve for local disks.

    'media' => [
        'max_upload_bytes' => 52_428_800, // 50 MiB
        'variant_decode_max_bytes' => 20_971_520, // 20 MiB; GD decode remains intentionally bounded.
    ],

    'marketplace' => [
        'max_catalog_bytes' => 8_388_608, // 8 MiB bounded JSON catalog before decode/normalization.
        'max_download_bytes' => 52_428_800, // Must remain within Sentinel quarantine upload policy.
    ],

    'archives' => [
        'theme' => [
            'max_source_bytes' => 52_428_800,
            'max_entries' => 1_000,
            'max_total_uncompressed_bytes' => 134_217_728,
            'max_entry_uncompressed_bytes' => 20_971_520,
            'max_text_entry_bytes' => 2_097_152,
            'max_compression_ratio' => 100,
        ],
        'extension' => [
            'max_source_bytes' => 134_217_728,
            'max_entries' => 5_000,
            'max_total_uncompressed_bytes' => 536_870_912,
            'max_entry_uncompressed_bytes' => 67_108_864,
            'max_compression_ratio' => 200,
        ],
    ],

    'backup' => [
        'max_bytes' => 53_687_091_200, // 50 GiB guardrail; operators should use external tooling above this scale.
        'minimum_free_bytes' => 268_435_456, // 256 MiB reserve while staging local snapshots.
    ],
];
