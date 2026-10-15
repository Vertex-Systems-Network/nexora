<?php

declare(strict_types=1);

/**
 * Resolve the same explicitly activated .env source as Nexora's installer
 * bootstrap. An installed fallback deployment must never be preflighted
 * against a stale/example root .env instead of its effective environment.
 *
 * @throws RuntimeException On invalid or missing explicit active sources.
 */
function nexoraBootstrapEffectiveEnvironmentFile(string $root): ?string
{
    $rootEnv = $root.'/.env';
    $protectedDir = $root.'/storage/app/nexora/environment';
    $fallbackEnv = $protectedDir.'/.env';
    $markerPath = $protectedDir.'/active';

    $active = '';
    if (file_exists($markerPath) || is_link($markerPath)) {
        if (! is_file($markerPath) || ! is_readable($markerPath)) {
            throw new RuntimeException('The protected environment active marker is unreadable.');
        }
        $active = strtolower(trim((string) file_get_contents($markerPath)));
        if (! in_array($active, ['root', 'fallback'], true)) {
            throw new RuntimeException('The protected environment active marker is invalid.');
        }
    }

    if ($active === 'fallback') {
        if (! is_file($fallbackEnv) || ! is_readable($fallbackEnv)) {
            throw new RuntimeException('The active protected fallback environment is missing or unreadable.');
        }
        return $fallbackEnv;
    }
    if ($active === 'root') {
        if (! is_file($rootEnv) || ! is_readable($rootEnv)) {
            throw new RuntimeException('The active root environment is missing or unreadable.');
        }
        return $rootEnv;
    }
    foreach ([$rootEnv, $fallbackEnv] as $path) {
        if (is_file($path) && is_readable($path)) {
            return $path;
        }
    }
    return null;
}

/**
 * Pre-Composer, pre-Laravel source-bootstrap URL/cookie safety checks.
 * Read-only: environment editing and existing key rotation remain operator-owned.
 *
 * @param array<string,string> $overrides Explicit hosting/process environment values.
 * @return list<string> Non-secret validation messages.
 */
function nexoraBootstrapOriginErrors(string $environment, array $overrides = []): array
{
    $settings = [];
    foreach (preg_split('/\r\n|\n|\r/', $environment) ?: [] as $line) {
        if (preg_match('/^\s*([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches) !== 1) {
            continue;
        }
        $value = trim($matches[2]);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $settings[$matches[1]] = $value;
    }

    // Nexora's pre-Laravel bootstrap gives explicit process environment values
    // precedence over .env. Verify those *effective* values instead of
    // rejecting a valid container-hosted installation's unused sample file.
    foreach (['APP_ENV', 'APP_URL', 'SESSION_SECURE_COOKIE', 'NEXORA_ALLOW_INSECURE_HTTP'] as $name) {
        if (isset($overrides[$name]) && $overrides[$name] !== '') {
            $settings[$name] = $overrides[$name];
        }
    }

    $url = $settings['APP_URL'] ?? '';
    $errors = [];
    $origin = parse_url($url);
    if ($url === '' || $origin === false || ! isset($origin['scheme'], $origin['host'])) {
        return ['APP_URL must be an absolute http:// or https:// origin configured for this host.'];
    }
    $scheme = strtolower($origin['scheme']);
    $host = strtolower($origin['host']);
    $isBracketedIp = str_starts_with($host, '[') && str_ends_with($host, ']');
    $ipv6 = $isBracketedIp ? substr($host, 1, -1) : '';
    $validIpv6 = $isBracketedIp && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    $validDns = preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/i', $host) === 1
        && strlen($host) <= 253
        && ! str_contains($host, '..')
        && array_reduce(explode('.', $host), static function (bool $ok, string $label): bool {
            return $ok && strlen($label) <= 63
                && preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $label) === 1;
        }, true);
    $validPort = ! isset($origin['port']) || ($origin['port'] >= 1 && $origin['port'] <= 65535);
    if (preg_match('/:[\s]*$/', $url) === 1) {
        $validPort = false;
    }
    if (! in_array($scheme, ['http', 'https'], true)
        || (! $validDns && ! $validIpv6)
        || ! $validPort
        || isset($origin['user']) || isset($origin['pass'])
        || isset($origin['query']) || isset($origin['fragment'])
        || (isset($origin['path']) && ! in_array($origin['path'], ['', '/'], true))) {
        return ['APP_URL must be a plain http(s) origin without credentials, path, query or fragment.'];
    }
    if (in_array($host, ['nexora.test', 'example.com', 'example.test'], true)) {
        $errors[] = 'APP_URL is a template hostname. Configure the actual local or HTTPS target origin before running bootstrap.';
    }

    $environmentMode = strtolower($settings['APP_ENV'] ?? '');
    if ($environmentMode === 'production' && strtolower($settings['NEXORA_ALLOW_INSECURE_HTTP'] ?? '') === 'true') {
        $errors[] = 'Production bootstrap must not opt into insecure HTTP.';
    }
    $cookie = strtolower($settings['SESSION_SECURE_COOKIE'] ?? '');
    if (! in_array($cookie, ['true', 'false'], true)) {
        $errors[] = 'SESSION_SECURE_COOKIE must be explicitly true or false.';
    } elseif ($scheme === 'https' && $cookie !== 'true') {
        $errors[] = 'HTTPS APP_URL requires SESSION_SECURE_COOKIE=true.';
    } elseif ($scheme === 'http') {
        if ($environmentMode === 'production') {
            $errors[] = 'Production bootstrap requires an HTTPS APP_URL.';
        }
        if ($cookie !== 'false') {
            $errors[] = 'HTTP APP_URL requires SESSION_SECURE_COOKIE=false so browser login can persist.';
        }
        $loopback = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
        if (! $loopback && strtolower($settings['NEXORA_ALLOW_INSECURE_HTTP'] ?? '') !== 'true') {
            $errors[] = 'Non-loopback HTTP requires an explicit NEXORA_ALLOW_INSECURE_HTTP=true in a nonproduction environment.';
        }
    }

    return $errors;
}
