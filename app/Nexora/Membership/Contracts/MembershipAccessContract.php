<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Contracts;

use App\Models\User;

interface MembershipAccessContract
{
    public function canAccess(?User $user, string $resourceType, string $resourceId): bool;
    public function assertCanAccess(?User $user, string $resourceType, string $resourceId): void;
}
