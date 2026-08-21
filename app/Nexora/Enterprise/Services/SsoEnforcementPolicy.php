<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseOrganizationMember;
use App\Models\EnterpriseSsoProvider;
use App\Models\User;

final class SsoEnforcementPolicy
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly SsoProviderRegistry $registry,
    ) {}

    public function requiresSso(User $user): bool
    {
        // Preserve a platform-level break-glass path. Organization SSO policy
        // must never make the sole Super Admin recovery identity unreachable.
        if ($user->hasRole('super-admin')) {
            return false;
        }

        $organization = $this->tenant->organization();
        if ($organization === null || $organization->status !== 'active') {
            return false;
        }

        $isMember = EnterpriseOrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            return false;
        }

        // Enforcement remains fail-closed even if the configured adapter has
        // disappeared. A broken adapter must not silently reopen password auth.
        return EnterpriseSsoProvider::query()
            ->where('organization_id', $organization->id)
            ->where('enabled', true)
            ->where('enforce_for_members', true)
            ->exists();
    }

    /**
     * @return array{
     *   organization: array{id:string,name:string,slug:string}|null,
     *   required: bool,
     *   providers: list<array{name:string,slug:string,protocol:string,href:string}>
     * }
     */
    public function loginContext(): array
    {
        $organization = $this->tenant->organization();
        if ($organization === null || $organization->status !== 'active') {
            return ['organization' => null, 'required' => false, 'providers' => []];
        }

        $providers = EnterpriseSsoProvider::query()
            ->where('organization_id', $organization->id)
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->filter(function (EnterpriseSsoProvider $provider): bool {
                $adapter = $this->registry->get($provider->adapter_key);

                return $adapter !== null && $adapter->protocol() === $provider->protocol;
            })
            ->map(fn (EnterpriseSsoProvider $provider): array => [
                'name' => $provider->name,
                'slug' => $provider->slug,
                'protocol' => $provider->protocol,
                'href' => route('enterprise.sso.start', [
                    'organization' => $organization->slug,
                    'provider' => $provider->slug,
                ]),
            ])
            ->values()
            ->all();

        $required = EnterpriseSsoProvider::query()
            ->where('organization_id', $organization->id)
            ->where('enabled', true)
            ->where('enforce_for_members', true)
            ->exists();

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'required' => $required,
            'providers' => $providers,
        ];
    }
}
