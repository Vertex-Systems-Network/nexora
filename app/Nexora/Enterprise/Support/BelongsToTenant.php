<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Support;

use App\Models\EnterpriseOrganization;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('nexora_tenant', static function (Builder $builder): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();
            if ($tenantId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
        });

        static::creating(static function ($model): void {
            $explicitTenantId = $model->getAttribute('tenant_id');
            if (is_string($explicitTenantId) && $explicitTenantId !== '') {
                self::assertTenantExists($explicitTenantId);

                return;
            }

            $tenantId = self::tenantIdForWrite();
            if ($tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    public function enterpriseOrganization(): BelongsTo
    {
        return $this->belongsTo(EnterpriseOrganization::class, 'tenant_id');
    }

    private static function tenantIdForWrite(): ?string
    {
        if (! Schema::hasTable('nx_enterprise_organizations')) {
            return null;
        }

        if (app()->bound(TenantContext::class)) {
            $context = app(TenantContext::class);
            $tenantId = $context->id();

            if ($tenantId !== null) {
                self::assertTenantExists($tenantId);

                return $tenantId;
            }
        }

        $defaultTenantId = EnterpriseOrganization::query()
            ->where('is_default', true)
            ->value('id');

        if (is_string($defaultTenantId) && $defaultTenantId !== '') {
            return $defaultTenantId;
        }

        throw new RuntimeException(
            'Nexora cannot create tenant-scoped data because no active tenant context or default organization exists.',
        );
    }

    private static function assertTenantExists(string $tenantId): void
    {
        $exists = EnterpriseOrganization::query()
            ->whereKey($tenantId)
            ->exists();

        if ($exists) {
            return;
        }

        throw new RuntimeException(
            'The active Nexora tenant context points to an organization that no longer exists. '
            .'Clear or re-resolve the tenant context before writing tenant-scoped data.',
        );
    }
}
