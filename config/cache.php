<?php

use Illuminate\Support\Str;

$nexoraVersion = (string) ((require __DIR__.'/nexora.php')['version'] ?? 'unknown');
$nexoraReleaseGeneration = null;
$nexoraReleasePath = dirname(__DIR__).'/nexora-release.json';
if (is_file($nexoraReleasePath)) {
    try {
        $nexoraRelease = json_decode((string) file_get_contents($nexoraReleasePath), true, 128, JSON_THROW_ON_ERROR);
        $candidate = strtolower(trim((string) ($nexoraRelease['runtime_deployment']['generation'] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $candidate) === 1) $nexoraReleaseGeneration = $candidate;
    } catch (Throwable) { $nexoraReleaseGeneration = null; }
}
$nexoraCacheFence = filter_var(env('NEXORA_CACHE_GENERATION_FENCING', true), FILTER_VALIDATE_BOOL);
$nexoraCacheBase = rtrim((string) env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache'), '-');
$nexoraPersistentGeneration = null;
foreach ([dirname(__DIR__).'/storage/app/nexora/update-trust/admission.json', dirname(__DIR__).'/storage/app/nexora/installed.lock'] as $statePath) {
    if (! is_file($statePath)) continue;
    try {
        $state = json_decode((string) file_get_contents($statePath), true, 128, JSON_THROW_ON_ERROR);
        $stateVersion = (string) ($state['target_version'] ?? $state['version'] ?? '');
        $candidate = strtolower(trim((string) ($state['target_deployment_generation'] ?? $state['deployment_generation'] ?? '')));
        if ($stateVersion === $nexoraVersion && preg_match('/^[a-f0-9]{64}$/', $candidate) === 1) { $nexoraPersistentGeneration = $candidate; break; }
    } catch (Throwable) { /* Ignore malformed optional runtime state here; runtime guards fail closed later. */ }
}
$nexoraCacheEpoch = trim((string) env('NEXORA_CACHE_EPOCH', $nexoraReleaseGeneration ?? $nexoraPersistentGeneration ?? $nexoraVersion));
$nexoraCachePrefix = $nexoraCacheFence ? $nexoraCacheBase.'-g'.substr(hash('sha256', $nexoraCacheEpoch), 0, 16).'-' : $nexoraCacheBase.'-';

$nexoraInstallPending = ! filter_var(env('NEXORA_INSTALLER_BYPASS', false), FILTER_VALIDATE_BOOL)
    && ! is_file(env('NEXORA_INSTALL_LOCK', dirname(__DIR__).'/storage/app/nexora/installed.lock'));

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => $nexoraInstallPending ? 'file' : env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => $nexoraCachePrefix,

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This value determines the classes that can be unserialized from cache
    | storage. By default, no PHP classes will be unserialized from your
    | cache to prevent gadget chain attacks if your APP_KEY is leaked.
    |
    */

    'serializable_classes' => false,

];
