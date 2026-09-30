<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseScimToken;
use Illuminate\Support\Str;

final class ScimTokenManager
{
    /** @return array{record:EnterpriseScimToken,token:string} */
    public function issue(EnterpriseOrganization $organization, string $name, ?string $expiresAt = null): array
    {
        if ($organization->status !== 'active') {
            throw new \RuntimeException('SCIM tokens cannot be issued for an inactive organization.');
        }

        $token = 'nxscim_'.Str::random(64);
        $record = EnterpriseScimToken::query()->create([
            'id' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'name' => $name,
            'token_hash' => hash('sha256', $token),
            'enabled' => true,
            'expires_at' => $expiresAt ?: null,
        ]);

        return ['record' => $record, 'token' => $token];
    }

    public function resolve(string $plain): ?EnterpriseScimToken
    {
        if (! str_starts_with($plain, 'nxscim_')) {
            return null;
        }

        $record = EnterpriseScimToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('enabled', true)
            ->whereNull('revoked_at')
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->first();

        if (! $record || ($record->expires_at && $record->expires_at->isPast())) {
            return null;
        }

        $record->forceFill(['last_used_at' => now()])->save();

        return $record;
    }
}
