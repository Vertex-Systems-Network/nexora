<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MembershipEntitlement extends Model
{
    use HasUuids;
    protected $table='nx_membership_entitlements'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['value'=>'array','active'=>'boolean']; }
    public function plan(): BelongsTo { return $this->belongsTo(MembershipPlan::class,'plan_id'); }
}
