<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use App\Nexora\Api\Services\ApiTokenManager;
use App\Nexora\Enterprise\Services\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function __construct(
        private readonly ApiTokenManager $tokens,
        private readonly TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = trim((string) $request->bearerToken());
        $token = $plain !== '' ? $this->tokens->resolve($plain) : null;
        if (! $token) {
            return $this->error('Invalid or expired API bearer token.', 401);
        }

        $rateKey = 'nexora-api-token:'.$token->id;
        if (RateLimiter::tooManyAttempts($rateKey, 120)) {
            $retry = max(1, RateLimiter::availableIn($rateKey));
            return $this->error('API rate limit exceeded.', 429)
                ->header('Retry-After', (string) $retry)
                ->header('X-RateLimit-Limit', '120');
        }
        RateLimiter::hit($rateKey, 60);

        $organization = $token->enterpriseOrganization;
        $user = $token->user;
        if (! $organization || ! $user) {
            return $this->error('Invalid or expired API bearer token.', 401);
        }

        $previousOrganization = $this->tenant->organization();
        $previousUser = Auth::user();
        $this->tenant->set($organization);
        Auth::setUser($user);
        $request->attributes->set(ApiAccessToken::class, $token);

        try {
            $response = $next($request);
            $remaining = max(0, 120 - RateLimiter::attempts($rateKey));
            $response->headers->set('X-RateLimit-Limit', '120');
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
            $response->headers->set('X-Nexora-Api-Version', 'v1');
            return $response;
        } finally {
            $this->tenant->set($previousOrganization);
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetUser();
            }
        }
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $status === 429 ? 'rate_limited' : 'invalid_token',
                'message' => $message,
            ],
        ], $status);
    }
}
