<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function show(Request $request, TenantContext $tenant): JsonResponse
    {
        $user = $request->user();
        $token = $request->attributes->get(ApiAccessToken::class);
        abort_unless($user !== null && $token instanceof ApiAccessToken, 401);
        $organization = $tenant->organization();

        // Explicit allow-list: never serialize the model (password hash, remember token, internals).
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'organization' => $organization?->only(['id', 'name', 'slug']),
                'token' => [
                    'name' => $token->name,
                    'abilities' => array_values((array) $token->abilities),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                ],
            ],
        ]);
    }
}
