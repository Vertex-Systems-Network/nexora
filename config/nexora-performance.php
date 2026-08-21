<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default): mixed {
    $value = getenv($key);
    if ($value === false || $value === '') return $default;
    if (is_bool($default)) return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    if (is_int($default)) return (int) $value;
    return $value;
};

return [
    /*
    |--------------------------------------------------------------------------
    | Release-candidate asset budgets
    |--------------------------------------------------------------------------
    |
    | These are intentionally generous release ceilings, not a claim that every
    | page ships the entire allowance. The initial JS budget follows only the
    | app entry's static import graph; lazy Inertia pages remain outside that
    | first-load graph and are measured by the total/per-asset budgets below.
    |
    */
    'budgets' => [
        'build_total_bytes' => (int) $env('NEXORA_BUDGET_BUILD_TOTAL_BYTES', 6_000_000),
        'javascript_total_bytes' => (int) $env('NEXORA_BUDGET_JS_TOTAL_BYTES', 3_500_000),
        'javascript_asset_bytes' => (int) $env('NEXORA_BUDGET_JS_ASSET_BYTES', 1_500_000),
        'javascript_gzip_total_bytes' => (int) $env('NEXORA_BUDGET_JS_GZIP_TOTAL_BYTES', 1_250_000),
        'initial_javascript_gzip_bytes' => (int) $env('NEXORA_BUDGET_INITIAL_JS_GZIP_BYTES', 900_000),
        'css_total_bytes' => (int) $env('NEXORA_BUDGET_CSS_TOTAL_BYTES', 750_000),
        'css_asset_bytes' => (int) $env('NEXORA_BUDGET_CSS_ASSET_BYTES', 500_000),
        'font_asset_bytes' => (int) $env('NEXORA_BUDGET_FONT_ASSET_BYTES', 350_000),
        'image_asset_bytes' => (int) $env('NEXORA_BUDGET_IMAGE_ASSET_BYTES', 1_750_000),
        'static_public_asset_bytes' => (int) $env('NEXORA_BUDGET_STATIC_PUBLIC_ASSET_BYTES', 1_750_000),
        'max_javascript_assets' => (int) $env('NEXORA_BUDGET_MAX_JS_ASSETS', 64),
        'max_css_assets' => (int) $env('NEXORA_BUDGET_MAX_CSS_ASSETS', 32),
    ],

    'http' => [
        'smoke_max_ms' => (int) $env('NEXORA_HTTP_SMOKE_MAX_MS', 2000),
        'server_timing' => (bool) $env('NEXORA_SERVER_TIMING', false),
        'query_budgets' => [
            'health_live' => (int) $env('NEXORA_QUERY_BUDGET_HEALTH_LIVE', 15),
            'login' => (int) $env('NEXORA_QUERY_BUDGET_LOGIN', 20),
        ],
    ],

    'headers' => [
        'hsts' => (bool) $env('NEXORA_HSTS', true),
        'hsts_max_age' => (int) $env('NEXORA_HSTS_MAX_AGE', 31536000),
        'hsts_include_subdomains' => (bool) $env('NEXORA_HSTS_INCLUDE_SUBDOMAINS', true),
    ],
];
