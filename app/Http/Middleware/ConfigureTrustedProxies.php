<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConfigureTrustedProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $proxies = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) config('nexora-runtime.http.trusted_proxies', []),
        )));

        foreach ($proxies as $proxy) {
            if ($proxy === '*' || str_contains($proxy, '://')) {
                throw new \RuntimeException('Nexora trusted proxies must be explicit IP/CIDR entries; wildcard or URL proxy declarations are not allowed.');
            }
        }

        Request::setTrustedProxies(
            $proxies,
            Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        return $next($request);
    }
}
