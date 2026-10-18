<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Api\Services\ApiAbilityRegistry;
use App\Nexora\Api\Services\ApiTokenManager;
use App\Nexora\Enterprise\Services\SsoEnforcementPolicy;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\TwoFactor\TwoFactorManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * MOBILE-AUTH-API-100: native-app credential exchange on the existing nxapi_ token layer.
 * Mirrors web-login policy (active status, SSO-required, audit) and refuses unverified email.
 * See .ai/security/threat-models/MOBILE-AUTH-API-100.md.
 */
final class AuthTokenController extends Controller
{
    public const TOKEN_NAME_PREFIX = 'Mobile · ';

    public const TOKEN_TTL_DAYS = 30;

    public const MAX_ACTIVE_TOKENS = 10;

    private const EMAIL_ATTEMPTS = 5;

    private const IP_ATTEMPTS = 20;

    private const ACCOUNT_ATTEMPTS = 30;

    private const ACCOUNT_DECAY_SECONDS = 900;

    // bcrypt of a discarded random secret; only used to equalise unknown-email timing.
    private const TIMING_DUMMY_HASH = '$2y$12$XRUyfn3oZEdfzE1wK0WTu.gCJJV9ULxENI5JX82JIIzCNvkaAqHai';

    public function store(
        Request $request,
        ApiTokenManager $tokens,
        AuditManager $audit,
        SsoEnforcementPolicy $ssoPolicy,
        TenantContext $tenant,
        TwoFactorManager $twoFactor,
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'device_name' => ['required', 'string', 'max:80'],
            'organization' => ['nullable', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:32'],
        ]);

        $emailHash = hash('sha256', strtolower(trim($data['email'])));
        $emailKey = 'nexora-api-auth:email:'.$emailHash.'|'.$request->ip();
        $accountKey = 'nexora-api-auth:account:'.$emailHash;
        $ipKey = 'nexora-api-auth:ip:'.$request->ip();
        // Count first, then decide: concurrent requests cannot all pass a check-then-hit gap.
        // The account bucket ignores IP so distributed guessing against one email stays capped.
        $ipCount = RateLimiter::hit($ipKey, 60);
        $emailCount = RateLimiter::hit($emailKey, 60);
        $accountCount = RateLimiter::hit($accountKey, self::ACCOUNT_DECAY_SECONDS);
        if ($ipCount > self::IP_ATTEMPTS || $emailCount > self::EMAIL_ATTEMPTS || $accountCount > self::ACCOUNT_ATTEMPTS) {
            $retry = max(RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey), RateLimiter::availableIn($accountKey), 1);

            return $this->error('rate_limited', 'Too many sign-in attempts. Try again later.', 429)
                ->header('Retry-After', (string) $retry);
        }

        $user = User::query()->where('email', $data['email'])->first();
        // Hash a dummy value for unknown emails so response timing does not reveal account existence.
        $valid = $user !== null
            ? Hash::check($data['password'], (string) $user->password)
            : Hash::check($data['password'], self::TIMING_DUMMY_HASH) && false;

        if (! $valid) {
            $audit->record('api.auth.token_failed', metadata: ['email_hash' => $emailHash]);

            return $this->invalidCredentials();
        }

        // Policy checks run only after the password is proven, so they leak nothing to guessers.
        if ($user->status !== 'active') {
            $audit->record('api.auth.token_blocked', $user, ['reason' => 'inactive-account']);

            return $this->error('account_unavailable', 'This account is not available for sign in.', 403);
        }
        if (! $user->hasVerifiedEmail()) {
            $audit->record('api.auth.token_blocked', $user, ['reason' => 'email-unverified']);

            return $this->error('email_unverified', 'Verify your email address before signing in on a device.', 403);
        }

        $organization = $this->organizationFor($user, $data['organization'] ?? null);
        if (! $organization) {
            $audit->record('api.auth.token_blocked', $user, ['reason' => 'no-organization-access']);

            return $this->error('organization_unavailable', 'No active organization is available for this account.', 403);
        }

        // SsoEnforcementPolicy reads the tenant context, which API routes do not install;
        // evaluate it inside the organization the token will be scoped to.
        if ($tenant->runWith($organization, fn (): bool => $ssoPolicy->requiresSso($user))) {
            $audit->record('api.auth.token_blocked', $user, ['reason' => 'enterprise-sso-required']);

            return $this->error('sso_required', 'This organization requires SSO sign-in.', 403);
        }

        // SYS-MFA-TOTP: a confirmed second factor is required before any token exists.
        if ($twoFactor->enabled($user)) {
            $code = trim((string) ($data['code'] ?? ''));
            if ($code === '') {
                return $this->error('two_factor_required', 'Enter the code from your authenticator app.', 422);
            }
            $method = $twoFactor->verifyForLogin($user, $code);
            if ($method === null) {
                $audit->record('auth.two_factor.challenge_failed', $user, ['channel' => 'api']);

                return $this->invalidCredentials();
            }
            $audit->record($method === 'recovery' ? 'auth.two_factor.recovery_used' : 'auth.two_factor.challenge_passed', $user, ['channel' => 'api']);
        }

        // Only a fully successful exchange resets the per-email buckets; blocked states keep counting.
        RateLimiter::clear($emailKey);
        RateLimiter::clear($accountKey);

        $created = $tokens->issue(
            $organization,
            $user,
            self::TOKEN_NAME_PREFIX.trim($data['device_name']),
            [ApiAbilityRegistry::PROFILE_READ],
            self::TOKEN_TTL_DAYS,
        );
        $this->enforceTokenCap($user, $tokens);

        return response()->json([
            'data' => [
                'token' => $created['token'],
                'token_type' => 'Bearer',
                'expires_at' => $created['record']->expires_at?->toIso8601String(),
                'abilities' => $created['record']->abilities,
                'organization' => $organization->only(['id', 'name', 'slug']),
            ],
        ], 201);
    }

    public function destroy(Request $request, ApiTokenManager $tokens): JsonResponse
    {
        $token = $request->attributes->get(ApiAccessToken::class);
        $user = $request->user();
        if ($token instanceof ApiAccessToken && $user instanceof User) {
            $tokens->revoke($token, $user);
        }

        return response()->json(null, 204);
    }

    private function organizationFor(User $user, ?string $requested): ?EnterpriseOrganization
    {
        $memberships = EnterpriseOrganizationMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'));

        $requested = trim((string) $requested);
        if ($requested !== '') {
            $organization = EnterpriseOrganization::query()
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereKey($requested)->orWhere('slug', $requested))
                ->first();
            if (! $organization) {
                return null;
            }

            return $user->hasRole('super-admin') || (clone $memberships)->where('organization_id', $organization->id)->exists()
                ? $organization
                : null;
        }

        $member = $memberships->with('organization')->orderBy('joined_at')->first();
        if ($member?->organization) {
            return $member->organization;
        }

        return $user->hasRole('super-admin')
            ? EnterpriseOrganization::query()->where('is_default', true)->where('status', 'active')->first()
            : null;
    }

    private function enforceTokenCap(User $user, ApiTokenManager $tokens): void
    {
        ApiAccessToken::withoutGlobalScope('nexora_tenant')
            ->where('user_id', $user->id)
            ->where('name', 'like', self::TOKEN_NAME_PREFIX.'%')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            // Only tokens shaped like an exchange result; never admin-issued tokens that share the prefix.
            ->filter(fn (ApiAccessToken $token): bool => (array) $token->abilities === [ApiAbilityRegistry::PROFILE_READ])
            ->values()
            ->slice(self::MAX_ACTIVE_TOKENS)
            ->each(fn (ApiAccessToken $stale) => $tokens->revoke($stale, $user));
    }

    private function invalidCredentials(): JsonResponse
    {
        return $this->error('invalid_credentials', 'These credentials do not match our records.', 422);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
