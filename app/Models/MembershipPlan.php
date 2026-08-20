<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MembershipPlan extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_membership_plans'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['metadata'=>'array']; }
    public function entitlements(): HasMany { return $this->hasMany(MembershipEntitlement::class,'plan_id'); }
    public function memberships(): HasMany { return $this->hasMany(Membership::class,'plan_id'); }
    public function commercePrice(): BelongsTo { return $this->belongsTo(CommercePrice::class,'commerce_price_id'); }
}
