<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Environment;

use App\Nexora\Installation\InstallationState;

final readonly class EnvironmentDoctor
{
    public function __construct(private InstallationState $installation) {}

    /** @return array{status:string,errors:list<string>,warnings:list<string>,facts:array<string,mixed>} */
    public function inspect(bool $production = false): array
    {
        $errors = [];
        $warnings = [];
        $physicallyInstalled = is_file($this->installation->lockPath());
        $enforceProduction = $production || $physicallyInstalled;

        $markerPath = (string) config('nexora-environment.active_marker_path');
        $rootPath = (string) config('nexora-environment.root_path');
        $fallbackPath = (string) config('nexora-environment.fallback_path');
        $cachedConfigPath = (string) config('nexora-environment.cached_config_path');
        $mode = is_file($markerPath) ? strtolower(trim((string) @file_get_contents($markerPath))) : 'auto';
        $sourcePath = $this->sourcePath($mode, $rootPath, $fallbackPath);
        $metadata = $physicallyInstalled ? ($this->installation->metadata() ?? []) : [];

        if ($physicallyInstalled) {
            if (! in_array($mode, ['root', 'fallback'], true)) {
                $errors[] = 'Installed Nexora must have an explicit environment active marker (root or fallback).';
            }
            if ($sourcePath === null || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
                $errors[] = 'The active installed environment source is missing or unreadable.';
            }
            $expected = match ((string) ($metadata['environment_mode'] ?? '')) {
                'project-root' => 'root',
                'protected-fallback' => 'fallback',
                default => null,
            };
            if ($expected !== null && $mode !== $expected) {
                $errors[] = "Installed metadata expects environment mode [{$expected}] but active marker reports [{$mode}].";
            }
        }

        if (is_file($rootPath) && is_file($fallbackPath)) {
            $warnings[] = 'Both root and protected fallback environment files exist; only the active-marker source is authoritative.';
        }

        $persistedKeys = $sourcePath !== null && is_readable($sourcePath) ? $this->environmentKeys($sourcePath) : [];
        if ($physicallyInstalled) {
            foreach ((array) config('nexora-environment.required_persisted_keys', []) as $key) {
                if (! isset($persistedKeys[(string) $key])) {
                    $warnings[] = "Active environment source does not persist [{$key}]; process-level configuration may be supplying it.";
                }
            }
        }

        $configurationCached = app()->configurationIsCached();
        if ($configurationCached && $sourcePath !== null && is_file($sourcePath) && is_file($cachedConfigPath)) {
            $sourceMtime = (int) filemtime($sourcePath);
            $cacheMtime = (int) filemtime($cachedConfigPath);
            if ($sourceMtime > $cacheMtime) {
                $errors[] = 'Laravel config cache is older than the active environment file. Run php artisan optimize:clear and rebuild caches.';
            }
        }

        if ($enforceProduction) {
            $this->validateProduction($errors, $warnings);
        }

        $key = (string) config('app.key', '');
        $facts = [
            'installed' => $physicallyInstalled,
            'production_enforced' => $enforceProduction,
            'environment_mode' => $mode,
            'environment_source' => $sourcePath,
            'environment_source_readable' => $sourcePath !== null && is_readable($sourcePath),
            'configuration_cached' => $configurationCached,
            'app_env' => (string) config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'app_url' => (string) config('app.url'),
            'app_key_fingerprint' => $key !== '' ? substr(hash('sha256', $key), 0, 16) : null,
            'database_connection' => (string) config('database.default'),
            'session_driver' => (string) config('session.driver'),
            'cache_store' => (string) config('cache.default'),
            'queue_connection' => (string) config('queue.default'),
            'filesystem_disk' => (string) config('filesystems.default'),
        ];

        return [
            'status' => $errors === [] ? ($warnings === [] ? 'pass' : 'warn') : 'fail',
            'errors' => $errors,
            'warnings' => $warnings,
            'facts' => $facts,
        ];
    }

    /** @param list<string> $errors @param list<string> $warnings */
    private function validateProduction(array &$errors, array &$warnings): void
    {
        if ((string) config('app.env') !== 'production') {
            $errors[] = 'Installed/production Nexora must run with APP_ENV=production.';
        }
        if ((bool) config('app.debug')) {
            $errors[] = 'APP_DEBUG must be false in installed/production Nexora.';
        }
        $key = trim((string) config('app.key'));
        if ($key === '' || $key === 'base64:') {
            $errors[] = 'APP_KEY is missing or invalid.';
        }

        $url = trim((string) config('app.url'));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($url === '' || $host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            $errors[] = 'APP_URL must be an absolute HTTP(S) URL.';
        }
        $allowInsecure = (bool) config('nexora-environment.allow_insecure_http', false);
        if ($scheme !== 'https' && ! $allowInsecure) {
            $errors[] = 'Production APP_URL must use HTTPS unless NEXORA_ALLOW_INSECURE_HTTP=true is explicitly set for a controlled rehearsal.';
        }

        if (! (bool) config('session.encrypt')) $errors[] = 'SESSION_ENCRYPT must remain enabled.';
        if (! (bool) config('session.http_only')) $errors[] = 'SESSION_HTTP_ONLY must remain enabled.';
        if ($scheme === 'https' && ! (bool) config('session.secure')) $errors[] = 'HTTPS APP_URL requires SESSION_SECURE_COOKIE=true.';
        $sameSite = strtolower((string) config('session.same_site'));
        if (! in_array($sameSite, (array) config('nexora-environment.safe_session_same_site', ['lax', 'strict']), true)) {
            $errors[] = 'SESSION_SAME_SITE must be lax or strict for the production Admin/session boundary.';
        }

        $session = (string) config('session.driver');
        if (in_array($session, (array) config('nexora-environment.non_persistent_session_drivers', ['array']), true)) {
            $errors[] = "Session driver [{$session}] is not persistent enough for an installed deployment.";
        }
        $cache = (string) config('cache.default');
        if (in_array($cache, (array) config('nexora-environment.non_persistent_cache_stores', ['array', 'null']), true)) {
            $errors[] = "Cache store [{$cache}] is not valid for an installed deployment.";
        }
        $queue = (string) config('queue.default');
        if (in_array($queue, (array) config('nexora-environment.synchronous_queue_connections', ['sync']), true)) {
            $warnings[] = 'QUEUE_CONNECTION=sync is allowed only for constrained/single-node deployments; async queueing is required for HA certification.';
        }

        $db = (string) config('database.default');
        if ($db === '' || config("database.connections.{$db}") === null) {
            $errors[] = 'Default database connection is missing from the cached configuration.';
        } else {
            $database = trim((string) config("database.connections.{$db}.database"));
            if ($database === '') $errors[] = "Database name/path is empty for connection [{$db}].";
        }
    }

    private function sourcePath(string $mode, string $root, string $fallback): ?string
    {
        return match ($mode) {
            'root' => $root,
            'fallback' => $fallback,
            default => is_readable($root) ? $root : (is_readable($fallback) ? $fallback : null),
        };
    }

    /** @return array<string,true> */
    private function environmentKeys(string $path): array
    {
        $keys = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) continue;
            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if (preg_match('/^[A-Z0-9_]+$/', $key) === 1) $keys[$key] = true;
        }
        return $keys;
    }
}
