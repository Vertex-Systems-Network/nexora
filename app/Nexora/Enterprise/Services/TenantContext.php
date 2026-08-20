<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Services;

use App\Models\EnterpriseOrganization;
use Closure;

final class TenantContext
{
    private ?EnterpriseOrganization $organization = null;

    public function set(?EnterpriseOrganization $organization): void
    {
        $this->organization = $organization;
    }

    public function clear(): void
    {
        $this->organization = null;
    }

    public function organization(): ?EnterpriseOrganization
    {
        return $this->organization;
    }

    public function id(): ?string
    {
        return $this->organization?->id;
    }

    public function active(): bool
    {
        return $this->organization !== null;
    }

    public function runWith(EnterpriseOrganization $organization, Closure $callback): mixed
    {
        $previous = $this->organization;
        $this->organization = $organization;

        try {
            return $callback();
        } finally {
            $this->organization = $previous;
        }
    }
}
