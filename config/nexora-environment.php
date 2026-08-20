<?php

declare(strict_types=1);

return [
    // Local HTTP can be enabled explicitly for development/rehearsal only.
    'allow_insecure_http' => filter_var(env('NEXORA_ALLOW_INSECURE_HTTP', false), FILTER_VALIDATE_BOOL),

    'required_persisted_keys' => [
        'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'APP_LOCALE',
        'DB_CONNECTION', 'DB_DATABASE',
        'SESSION_DRIVER', 'SESSION_ENCRYPT', 'SESSION_HTTP_ONLY', 'SESSION_SECURE_COOKIE', 'SESSION_SAME_SITE',
        'CACHE_STORE', 'QUEUE_CONNECTION', 'FILESYSTEM_DISK',
    ],

    'secret_keys' => [
        'APP_KEY', 'APP_PREVIOUS_KEYS', 'DB_PASSWORD', 'DB_URL', 'REDIS_PASSWORD', 'REDIS_URL', 'MAIL_PASSWORD', 'MAIL_URL', 'HTTP_PROXY', 'HTTPS_PROXY',
        'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_SESSION_TOKEN', 'POSTMARK_API_KEY', 'RESEND_API_KEY',
        'SLACK_BOT_USER_OAUTH_TOKEN', 'LOG_SLACK_WEBHOOK_URL',
    ],

    'safe_session_same_site' => ['lax', 'strict'],
    'non_persistent_session_drivers' => ['array'],
    'non_persistent_cache_stores' => ['array', 'null'],
    'synchronous_queue_connections' => ['sync'],

    'root_path' => base_path('.env'),
    'fallback_path' => storage_path('app/nexora/environment/.env'),
    'active_marker_path' => storage_path('app/nexora/environment/active'),
    'cached_config_path' => base_path('bootstrap/cache/config.php'),
];
