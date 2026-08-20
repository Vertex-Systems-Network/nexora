<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class EnforceRequestLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawLength = trim((string) $request->server('CONTENT_LENGTH', ''));
        if ($rawLength !== '') {
            if (! ctype_digit($rawLength)) {
                throw new BadRequestHttpException('Invalid Content-Length header.');
            }

            $length = (int) $rawLength;
            $maximum = (int) config('nexora-runtime.http.max_body_bytes', 67_108_864);
            if ($length > $maximum) {
                throw new HttpException(413, 'Request payload exceeds the configured Nexora HTTP body limit.');
            }
        }

        return $next($request);
    }
}
