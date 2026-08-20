<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HelpdeskSlaPolicy extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'nx_helpdesk_sla_policies';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(HelpdeskTicket::class, 'sla_policy_id');
    }
}
