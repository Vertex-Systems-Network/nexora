<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\User;
use Illuminate\Support\Collection;

final readonly class TenantMemberDirectory
{
    public function __construct(private TenantContext $tenant) {}

    /** @return Collection<int, User> */
    public function activeUsers(): Collection
    {
        $tenantId = $this->tenant->id();
        if ($tenantId === null) {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->whereHas('enterpriseMemberships', fn ($membership) => $membership
                ->where('organization_id', $tenantId)
                ->where('status', 'active'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** @return Collection<int, User> */
    public function search(string $query, int $limit = 20): Collection
    {
        $tenantId = $this->tenant->id();
        $query = trim($query);
        if ($tenantId === null || $query === '') {
            return collect();
        }

        $limit = max(1, min(100, $limit));

        return User::query()
            ->where('status', 'active')
            ->whereHas('enterpriseMemberships', fn ($membership) => $membership
                ->where('organization_id', $tenantId)
                ->where('status', 'active'))
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }

    /** @return array<int, array{id:int,name:string}> */
    public function options(bool $includeEmail = false): array
    {
        return $this->activeUsers()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $includeEmail ? $user->name.' · '.$user->email : $user->name,
            ])
            ->all();
    }
}
