<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireApiAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = $request->attributes->get(ApiAccessToken::class);
        if (! $token instanceof ApiAccessToken || ! $token->allows($ability)) {
            return response()->json([
                'error' => [
                    'code' => 'insufficient_scope',
                    'message' => 'The API token does not grant the required ability.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
