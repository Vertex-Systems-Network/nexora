<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Nexora\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfNotInstalled
{
    public function __construct(private InstallationState $state)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->isInstalled()) {
            $inspection = $this->state->inspect();
            if (($inspection['valid'] ?? false) !== true) {
                $message = 'Nexora installation lock failed integrity validation. '
                    .'The installer remains closed. Run `php artisan nexora:install:lock-status`.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'installation_lock_status' => $inspection['status'] ?? 'invalid',
                    ], 503);
                }

                return response($message, 503, [
                    'Cache-Control' => 'no-store',
                    'X-Nexora-Installation-Lock' => 'invalid',
                ]);
            }
        } elseif (! $request->routeIs('install.*') && ! $request->routeIs('locale.update')) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
