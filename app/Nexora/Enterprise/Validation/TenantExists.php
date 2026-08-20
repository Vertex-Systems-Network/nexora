<?php

declare(strict_types=1);

namespace App\Nexora\Enterprise\Validation;

use App\Nexora\Enterprise\Services\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

final class TenantExists implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'id',
        private readonly ?string $throughTable = null,
        private readonly ?string $throughForeignKey = null,
        private readonly string $throughOwnerKey = 'id',
    ) {}

    public static function through(
        string $table,
        string $throughTable,
        string $throughForeignKey,
        string $column = 'id',
        string $throughOwnerKey = 'id',
    ): self {
        return new self($table, $column, $throughTable, $throughForeignKey, $throughOwnerKey);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tenantId = app(TenantContext::class)->id();
        if ($tenantId === null) {
            $fail('The selected :attribute is not available in the current organization.');
            return;
        }

        if ($this->throughTable === null) {
            $exists = DB::table($this->table)
                ->where($this->column, $value)
                ->where('tenant_id', $tenantId)
                ->exists();
        } else {
            if ($this->throughForeignKey === null) {
                $fail('The selected :attribute could not be validated.');
                return;
            }

            $exists = DB::table($this->table.' as target')
                ->join(
                    $this->throughTable.' as tenant_parent',
                    'target.'.$this->throughForeignKey,
                    '=',
                    'tenant_parent.'.$this->throughOwnerKey,
                )
                ->where('target.'.$this->column, $value)
                ->where('tenant_parent.tenant_id', $tenantId)
                ->exists();
        }

        if (! $exists) {
            $fail('The selected :attribute is not available in the current organization.');
        }
    }
}
