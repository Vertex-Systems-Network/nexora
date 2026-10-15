<?php

declare(strict_types=1);

/**
 * Pre-Composer, pre-Laravel source-bootstrap URL/cookie safety checks.
 * Read-only: environment editing and existing key rotation remain operator-owned.
 *
 * @return list<string> Non-secret validation messages.
 */
function nexoraBootstrapOriginErrors(string $environment): array
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

    $url = $settings['APP_URL'] ?? '';
    $errors = [];
    $origin = parse_url($url);
    if ($url === '' || $origin === false || ! isset($origin['scheme'], $origin['host'])) {
        return ['APP_URL must be an absolute http:// or https:// origin configured for this host.'];
    }
    $scheme = strtolower($origin['scheme']);
    $host = strtolower($origin['host']);
    if (! in_array($scheme, ['http', 'https'], true)
        || preg_match('/^[a-z0-9][a-z0-9.-]*$/i', $host) !== 1
        || isset($origin['user']) || isset($origin['pass'])
        || isset($origin['query']) || isset($origin['fragment'])
        || (isset($origin['path']) && ! in_array($origin['path'], ['', '/'], true))) {
        return ['APP_URL must be a plain http(s) origin without credentials, path, query or fragment.'];
    }
    if (in_array($host, ['nexora.test', 'example.com', 'example.test'], true)) {
        $errors[] = 'APP_URL is a template hostname. Configure the actual local or HTTPS target origin before running bootstrap.';
    }

    $environmentMode = strtolower($settings['APP_ENV'] ?? '');
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
        $loopback = in_array($host, ['localhost', '127.0.0.1'], true);
        if (! $loopback && strtolower($settings['NEXORA_ALLOW_INSECURE_HTTP'] ?? '') !== 'true') {
            $errors[] = 'Non-loopback HTTP requires an explicit NEXORA_ALLOW_INSECURE_HTTP=true in a nonproduction environment.';
        }
    }

    return $errors;
}
