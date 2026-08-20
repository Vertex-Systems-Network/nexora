<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MembershipEvent extends Model
{
    use HasUuids;
    protected $table='nx_membership_events'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; public $timestamps=false;
    protected function casts(): array { return ['payload'=>'array','occurred_at'=>'datetime']; }
    public function membership(): BelongsTo { return $this->belongsTo(Membership::class,'membership_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class,'actor_id'); }
}
