<?php

declare(strict_types=1);

/**
 * Redact subprocess output BEFORE writing step logs, echoing text, or creating
 * the portable target evidence bundle. This is defence-in-depth, not a
 * substitute for preventing programs from printing credentials in the first
 * place. No secret values are ever retained by this function.
 */
function nexoraTargetRedactOutput(string $text): string
{
    // Keep private-key blocks out of portable step logs in their entirety,
    // including line-wrapped data and both PEM envelope lines.
    $text = (string) preg_replace(
        '~-----BEGIN ((?:[A-Z0-9]+ )*PRIVATE KEY)-----[\s\S]*?-----END \1-----~i',
        '[REDACTED PRIVATE KEY]', $text
    );

    // Header values are never needed in certification output.
    $text = (string) preg_replace(
        '~(\b(?:Authorization|Proxy-Authorization|X-Api-Key)\s*:\s*)[^\r\n]+~i',
        '$1[REDACTED]', $text
    );
    // Standalone HTTP auth tokens can be emitted without a header label.
    $text = (string) preg_replace(
        '~\b(Bearer|Basic)\s+[^\s,;]+~i', '$1 [REDACTED]', $text
    );

    // Laravel's APP_PREVIOUS_KEYS contains an entire comma-separated list of
    // previously valid encryption keys. A generic key=value matcher normally
    // stops at a comma and would leave later keys visible in step logs.
    // Also cover JSON arrays exported by config diagnostics. This runs before
    // the general assignment matcher and keeps quoted JSON structure valid.
    $text = (string) preg_replace_callback(
        '~(?P<key>["\']?APP_PREVIOUS_KEYS["\']?)(?P<sep>\s*[:=]\s*)(?P<value>\[[^\]\r\n]*\]|"[^"\r\n]*"|\'[^\'\r\n]*\'|[^\s;\r\n}]+)~i',
        static function (array $match): string {
            $value = $match['value'];
            // Preserve our plain-text mask across repeated scrub passes.
            if ($value === '[REDACTED]') return $match['key'].$match['sep'].$value;
            if (str_starts_with($value, '[')) {
                // Array-shaped config output must remain parseable JSON.
                return $match['key'].$match['sep'].'["[REDACTED]"]';
            }
            $quote = ($value[0] === '"' || $value[0] === "'") ? $value[0] : '';
            return $match['key'].$match['sep'].$quote.'[REDACTED]'.$quote;
        },
        $text
    );

    // Environment/JSON/YAML-style assignments, including APP_KEY=base64:...
    // and provider-specific credentials such as AWS_SECRET_ACCESS_KEY.
    $text = (string) preg_replace_callback(
        '~(?P<key>["\']?[A-Za-z_][A-Za-z0-9_-]{1,120}["\']?)(?P<sep>\s*[:=]\s*)(?P<value>"[^"\r\n]*"|\'[^\'\r\n]*\'|[^\s,;\r\n}]+)~',
        static function (array $match): string {
            $key = strtoupper(trim($match['key'], "\"'"));
            $secretKey = preg_match(
                '~(?:^|_)(?:KEY|PASSWORD|PASSWD|SECRET|TOKEN|CREDENTIAL|AUTHORIZATION|DSN)$~D',
                $key
            ) === 1 || in_array($key, [
                'AWS_ACCESS_KEY_ID', 'AWS_SESSION_TOKEN', 'DATABASE_URL',
                'REDIS_URL', 'MAIL_URL', 'SENTRY_LARAVEL_DSN', 'SENTRY_DSN',
                'AUTH_TOKEN', 'APIKEY', 'CLIENT_SECRET',
            ], true);
            if (!$secretKey) return $match[0];
            $value = $match['value'];
            $quote = ($value[0] === '"' || $value[0] === "'") ? $value[0] : '';
            return $match['key'].$match['sep'].$quote.'[REDACTED]'.$quote;
        },
        $text
    );

    // Query-string credentials can be exposed inside URLs and exception logs.
    $text = (string) preg_replace(
        '~([?&](?:access_token|refresh_token|id_token|token|password|passwd|secret|api[_-]?key|auth|signature|sig|credential)=)[^&#\s]+~i',
        '$1[REDACTED]', $text
    );
    // Passwords embedded in DSN/userinfo URLs without a labelled env key.
    $text = (string) preg_replace(
        '~(://[^:/@\s]+:)[^@/\s]+(@)~', '$1[REDACTED]$2', $text
    );
    return $text;
}

/**
 * Scrub command arguments in evidence separately from the real argv passed to
 * proc_open(). Secrets provided as a value after a credential switch must not
 * leak just because the switch/value are different argv elements.
 *
 * @param list<mixed> $parts
 * @return list<string>
 */
function nexoraTargetRedactCommandParts(array $parts): array
{
    $sanitized = [];
    $redactNext = false;
    foreach ($parts as $part) {
        $value = (string) $part;
        if ($redactNext) {
            $sanitized[] = '[REDACTED]';
            $redactNext = false;
            continue;
        }
        if (preg_match(
            '~^(--?(?:app[_-]?(?:key|previous[_-]?keys)|api[_-]?key|private[_-]?key|token|password|passwd|secret|credential|authorization|auth|client[_-]?secret))=.+$~i',
            $value, $credential
        ) === 1) {
            $sanitized[] = $credential[1].'=[REDACTED]';
            continue;
        }
        $redactNext = preg_match(
            '~^--?(?:app[_-]?(?:key|previous[_-]?keys)|api[_-]?key|private[_-]?key|token|password|passwd|secret|credential|authorization|auth|client[_-]?secret)$~i',
            $value
        ) === 1;
        $sanitized[] = nexoraTargetRedactOutput($value);
    }
    return $sanitized;
}
