<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Membership extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_memberships'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['started_at'=>'datetime','trial_ends_at'=>'datetime','ends_at'=>'datetime','cancelled_at'=>'datetime','metadata'=>'array']; }
    public function plan(): BelongsTo { return $this->belongsTo(MembershipPlan::class,'plan_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class,'user_id'); }
    public function commerceCustomer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class,'commerce_customer_id'); }
    public function commerceSubscription(): BelongsTo { return $this->belongsTo(CommerceSubscription::class,'commerce_subscription_id'); }
    public function events(): HasMany { return $this->hasMany(MembershipEvent::class,'membership_id'); }
    public function isEffective(): bool
    {
        if (! in_array($this->status,['active','trial'],true)) return false;
        $now=now();
        if ($this->started_at && $this->started_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->lte($now)) return false;
        if ($this->status==='trial' && $this->trial_ends_at && $this->trial_ends_at->lte($now)) return false;
        return true;
    }
}
