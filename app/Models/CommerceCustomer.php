<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceCustomer extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_customers';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['billing_address'=>'array','shipping_address'=>'array','metadata'=>'array']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function orders(): HasMany { return $this->hasMany(CommerceOrder::class, 'customer_id'); }
    public function subscriptions(): HasMany { return $this->hasMany(CommerceSubscription::class, 'customer_id'); }
}
