<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Developer;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Nexora\Api\Services\ApiAbilityRegistry;
use App\Nexora\Api\Services\ApiTokenManager;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApiTokenController extends Controller
{
    public function index(TenantContext $tenant, ApiAbilityRegistry $abilities): Response
    {
        $organization = $tenant->organization();
        abort_unless($organization !== null, 404);

        $tokens = ApiAccessToken::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(100)
            ->get()
            ->map(static fn (ApiAccessToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'hint' => $token->token_hint,
                'abilities' => $token->abilities ?? [],
                'user' => $token->user ? [
                    'id' => $token->user->id,
                    'name' => $token->user->name,
                    'email' => $token->user->email,
                ] : null,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'revoked_at' => $token->revoked_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Admin/Developer/ApiTokens', [
            'organization' => $organization->only(['id', 'name', 'slug']),
            'tokens' => $tokens,
            'abilities' => $abilities->all(),
            'baseUrl' => url('/api/v1'),
        ]);
    }

    public function store(Request $request, TenantContext $tenant, ApiTokenManager $tokens): JsonResponse
    {
        $organization = $tenant->organization();
        abort_unless($organization !== null && $request->user() !== null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'abilities' => ['required', 'array', 'min:1', 'max:20'],
            'abilities.*' => ['required', 'string', 'max:120'],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $created = $tokens->issue(
            $organization,
            $request->user(),
            $data['name'],
            $data['abilities'],
            (int) $data['expires_in_days'],
        );

        return response()->json([
            'token' => $created['token'],
            'record' => [
                'id' => $created['record']->id,
                'name' => $created['record']->name,
                'hint' => $created['record']->token_hint,
                'abilities' => $created['record']->abilities,
                'expires_at' => $created['record']->expires_at?->toIso8601String(),
            ],
            'warning' => 'Copy this token now. Nexora stores only its hash and cannot show the token again.',
        ], 201);
    }

    public function destroy(Request $request, string $token, ApiTokenManager $tokens): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor !== null, 404);

        // Resolve only after web tenant middleware has installed the active organization.
        // This keeps revocation scoped even when a UUID from another tenant is guessed.
        $record = ApiAccessToken::query()->whereKey($token)->firstOrFail();
        $tokens->revoke($record, $actor);

        return back()->with('success', 'API token revoked.');
    }
}
