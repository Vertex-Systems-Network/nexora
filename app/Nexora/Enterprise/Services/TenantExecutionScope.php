<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseOrganization;
use Closure;
use RuntimeException;

final class TenantExecutionScope
{
    public function __construct(private readonly TenantContext $context) {}

    public function runRequired(?string $tenantId, string $operation, Closure $callback): mixed
    {
        $normalizedTenantId = trim((string) $tenantId);

        if ($normalizedTenantId === '') {
            throw new RuntimeException(
                "Nexora cannot execute {$operation} because the queued record has no tenant identifier.",
            );
        }

        $organization = EnterpriseOrganization::query()
            ->whereKey($normalizedTenantId)
            ->where('status', 'active')
            ->first();

        if ($organization === null) {
            throw new RuntimeException(
                "Nexora cannot execute {$operation} because tenant {$normalizedTenantId} no longer exists or is not active.",
            );
        }

        return $this->context->runWith($organization, $callback);
    }
}
