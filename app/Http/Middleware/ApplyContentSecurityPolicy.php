<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY-BASELINE-200 / CSP increment: per-request nonce Content-Security-Policy
 * for HTML responses. Scripts run only from same origin or with the request nonce;
 * plugins, base-tag hijacking and cross-origin framing are refused. Responses that
 * already carry their own policy (e.g. sandboxed media) are left untouched.
 */
final class ApplyContentSecurityPolicy
{
    public const MODES = ['enforce', 'report-only', 'off'];

    public function handle(Request $request, Closure $next): Response
    {
        $mode = (string) config('nexora-performance.headers.csp', 'enforce');
        if (! in_array($mode, self::MODES, true)) {
            $mode = 'enforce';
        }

        if ($mode === 'off') {
            return $next($request);
        }

        $nonce = Vite::useCspNonce();
        $response = $next($request);

        if ($response->headers->has('Content-Security-Policy')
            || $response->headers->has('Content-Security-Policy-Report-Only')
            || ! $this->isHtml($response)) {
            return $response;
        }

        $header = $mode === 'report-only' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
        $response->headers->set($header, $this->policy($nonce));

        return $response;
    }

    public function policy(string $nonce): string
    {
        $dev = $this->devServerOrigins();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$dev['http']],
            'style-src' => ["'self'", "'unsafe-inline'", ...$dev['http']],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", ...$dev['http'], ...$dev['ws']],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'worker-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'", ...$this->extraOrigins('csp_form_action')],
            'frame-ancestors' => ["'self'"],
        ];

        return implode('; ', array_map(
            fn (string $name, array $sources): string => $name.' '.implode(' ', array_unique($sources)),
            array_keys($directives),
            $directives,
        ));
    }

    /** @return list<string> Explicit https origins from a comma-separated config value. */
    private function extraOrigins(string $key): array
    {
        $origins = [];
        foreach (explode(',', (string) config('nexora-performance.headers.'.$key, '')) as $candidate) {
            $parts = parse_url(trim($candidate));
            if (is_array($parts) && ($parts['scheme'] ?? '') === 'https' && isset($parts['host'])
                && in_array($parts['path'] ?? '/', ['', '/'], true) && ! isset($parts['query']) && ! isset($parts['user'])) {
                $origins[] = 'https://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            }
        }

        return $origins;
    }

    private function isHtml(Response $response): bool
    {
        $type = (string) $response->headers->get('Content-Type', '');

        return $type === '' || str_contains(strtolower($type), 'text/html');
    }

    /** @return array{http: list<string>, ws: list<string>} */
    private function devServerOrigins(): array
    {
        if (! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        $hot = trim((string) @file_get_contents(Vite::hotFile()));
        $parts = parse_url($hot);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return ['http' => [], 'ws' => []];
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        $ws = ($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return ['http' => [$origin], 'ws' => [$ws]];
    }
}
