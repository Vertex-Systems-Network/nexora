<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyPerformanceHeaders
{
    public function __construct(private readonly InstallationState $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() && (bool) config('nexora-performance.headers.hsts', true)) {
            $maxAge = max(0, (int) config('nexora-performance.headers.hsts_max_age', 31536000));
            $value = 'max-age='.$maxAge;
            if ((bool) config('nexora-performance.headers.hsts_include_subdomains', true)) {
                $value .= '; includeSubDomains';
            }
            $response->headers->set('Strict-Transport-Security', $value);
        }

        if ($this->mustNotCache($request, $response)) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        } elseif (! $response->headers->has('Cache-Control')) {
            // Public dynamic page caching remains conservative in RC9. N1.6 owns
            // explicit page/fragment/CDN caching so authorization context is
            // never accidentally cached by an intermediary during certification.
            $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        }

        if ((bool) config('nexora-performance.http.server_timing', false)) {
            $duration = round((microtime(true) - $started) * 1000, 2);
            $response->headers->set('Server-Timing', 'app;dur='.$duration);
        }

        return $response;
    }

    private function mustNotCache(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return true;
        }
        // Installer/bootstrap responses are always private/no-store. More
        // importantly, do not resolve the auth guard before installation: the
        // Eloquent user provider can touch a database that does not exist yet.
        if (! $this->installation->isInstalled()) {
            return true;
        }

        if ($response->getStatusCode() >= 400 || $request->user() !== null) {
            return true;
        }

        return $request->is(
            'admin',
            'admin/*',
            'install',
            'install/*',
            'login',
            'register',
            'forgot-password',
            'reset-password/*',
            'email/*',
            'sso/*',
            'scim/*',
            'hooks/*',
            'health/*',
            'enterprise/invitations/*',
        );
    }
}
