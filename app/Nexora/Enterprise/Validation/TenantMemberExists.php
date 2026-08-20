<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Validation;

use App\Nexora\Enterprise\Services\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class TenantMemberExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tenantId = app(TenantContext::class)->id();
        if ($tenantId === null) {
            $fail('The selected :attribute is not an active member of the current organization.');
            return;
        }

        $exists = DB::table('nx_enterprise_organization_members')
            ->join('users', 'users.id', '=', 'nx_enterprise_organization_members.user_id')
            ->where('nx_enterprise_organization_members.organization_id', $tenantId)
            ->where('nx_enterprise_organization_members.user_id', $value)
            ->where('nx_enterprise_organization_members.status', 'active')
            ->where('users.status', 'active')
            ->exists();

        if (! $exists) {
            $fail('The selected :attribute is not an active member of the current organization.');
        }
    }
}
