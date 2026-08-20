<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Contracts;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;

interface MembershipManagerContract
{
    public function grant(MembershipPlan $plan, ?User $user, array $attributes = [], ?int $actorId = null): Membership;
    public function setStatus(Membership $membership, string $status, ?int $actorId = null): Membership;
}
