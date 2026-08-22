<?php

declare(strict_types=1);

$root = dirname(__DIR__);

return [
    'bypass' => (bool) env('NEXORA_INSTALLER_BYPASS', false),
    'mutex_path' => $root.'/storage/app/nexora/installing.lock',
    'lock_path' => env('NEXORA_INSTALL_LOCK', $root.'/storage/app/nexora/installed.lock'),
    'lock_schema' => 2,
    'post_install_handoff_receipt_path' => $root.'/storage/app/nexora/runtime/post-install-handoff.json',

    'source' => [
        'expected_protocol' => 'v5.29',
        'expected_generation' => 'n1-v5.29',
        // Sealed after Installer.php is finalized for this source package.
        'installer_sha256' => '6837eae593fa2f3f7d6a8f11d93020d10ad34d753516b9f1bbeec019e13dde69',
        'manifest_path' => $root.'/bootstrap/nexora-source-manifest.json',
        'manifest_sha256' => 'e0349ae43cac1503fec0c049409698f7eda13c4fa861fb45344fb3afad4875ee',
        'activation_receipt_path' => $root.'/storage/app/nexora/source-activation/cli-activation.json',
        'web_ack_path' => $root.'/storage/app/nexora/source-activation/web-ack.json',
        'web_ack_token_path' => $root.'/storage/app/nexora/source-activation/web-ack.token',
        'activation_ttl_seconds' => 900,
    ],
    'environment_path' => $root.'/.env',
    'environment_fallback_path' => $root.'/storage/app/nexora/environment/.env',
    'environment_example_path' => $root.'/.env.example',
    'environment_marker_path' => $root.'/storage/app/nexora/environment/active',
    'environment_bootstrap_key_path' => $root.'/storage/app/nexora/environment/bootstrap.key',
    'required_php' => '8.3.0',
    'required_extensions' => ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml', 'zip'],
    'writable_paths' => [
        $root.'/storage',
        $root.'/bootstrap/cache',
    ],
    'runtime_paths' => [
        $root.'/storage/framework/views',
        $root.'/storage/framework/sessions',
        $root.'/storage/framework/cache/data',
        $root.'/storage/logs',
        $root.'/storage/app/nexora',
    ],
    'release_files' => [
        $root.'/vendor/autoload.php',
        $root.'/public/build/manifest.json',
    ],
    'allow_database_creation' => true,
    'run_stale_seconds' => 1800,
    'recovery_window_seconds' => 86400,
];
