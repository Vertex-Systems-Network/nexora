<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'nx_audit_logs';

    protected $fillable = [
        'tenant_id', 'user_id', 'event', 'subject_type', 'subject_id', 'ip_address',
        'user_agent', 'metadata', 'request_id', 'created_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('nexora_audit_tenant', static function (Builder $builder): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();
            if ($tenantId !== null) {
                $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
