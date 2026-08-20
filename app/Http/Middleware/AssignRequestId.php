<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public const ATTRIBUTE = 'nexora_request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->headers->get('X-Request-Id'));
        $requestId = preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $incoming) === 1
            ? $incoming
            : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
