<?php

declare(strict_types=1);

namespace App\Nexora\Api\Services;

use App\Models\ApiAccessToken;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Enterprise\Services\EnterpriseAuditRecorder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ApiTokenManager
{
    public function __construct(
        private readonly ApiAbilityRegistry $abilities,
        private readonly EnterpriseAuditRecorder $audit,
    ) {}

    /** @param array<int,mixed> $abilities @return array{record:ApiAccessToken,token:string} */
    public function issue(EnterpriseOrganization $organization, User $actor, string $name, array $abilities, int $expiresInDays): array
    {
        if ($organization->status !== 'active') {
            throw ValidationException::withMessages(['organization' => 'API tokens can only be issued for an active organization.']);
        }
        if ($actor->status !== 'active') {
            throw ValidationException::withMessages(['user' => 'API tokens require an active user.']);
        }
        if (! $actor->hasRole('super-admin') && ! EnterpriseOrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $actor->id)
            ->where('status', 'active')
            ->exists()) {
            throw ValidationException::withMessages(['user' => 'API tokens require active organization access.']);
        }
        if ($expiresInDays < 1 || $expiresInDays > 365) {
            throw ValidationException::withMessages(['expires_in_days' => 'API token expiry must be between 1 and 365 days.']);
        }

        $normalizedAbilities = $this->abilities->normalize($abilities);
        $plain = 'nxapi_'.Str::random(64);
        $record = ApiAccessToken::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $organization->id,
            'user_id' => $actor->id,
            'name' => trim($name),
            'token_hash' => hash('sha256', $plain),
            'token_hint' => 'nxapi_…'.substr($plain, -6),
            'abilities' => $normalizedAbilities,
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        $this->audit->record(
            'api.token.issued',
            $organization->id,
            $actor->id,
            'api_access_token',
            $record->id,
            ['abilities' => $normalizedAbilities, 'expires_in_days' => $expiresInDays],
        );

        return ['record' => $record, 'token' => $plain];
    }

    public function resolve(string $plain): ?ApiAccessToken
    {
        if (! str_starts_with($plain, 'nxapi_') || strlen($plain) !== 70) {
            return null;
        }

        $record = ApiAccessToken::withoutGlobalScope('nexora_tenant')
            ->with(['enterpriseOrganization', 'user'])
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();

        if (! $record || ! $record->expires_at || $record->expires_at->isPast()) {
            return null;
        }

        $organization = $record->enterpriseOrganization;
        $user = $record->user;
        if (! $organization || $organization->status !== 'active' || ! $user || $user->status !== 'active') {
            return null;
        }

        if (! $user->hasRole('super-admin') && ! EnterpriseOrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists()) {
            return null;
        }

        if (! $record->last_used_at || $record->last_used_at->lt(now()->subMinute())) {
            $record->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        return $record;
    }

    public function revoke(ApiAccessToken $token, User $actor): void
    {
        if ($token->revoked_at) {
            return;
        }

        $token->forceFill(['revoked_at' => now()])->save();
        $this->audit->record(
            'api.token.revoked',
            $token->tenant_id,
            $actor->id,
            'api_access_token',
            $token->id,
        );
    }
}
