<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommerceSubscription extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_subscriptions';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['cancel_at_period_end'=>'boolean','current_period_start'=>'datetime','current_period_end'=>'datetime','cancelled_at'=>'datetime','metadata'=>'array']; }
    public function customer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class, 'customer_id'); }
    public function product(): BelongsTo { return $this->belongsTo(CommerceProduct::class, 'product_id'); }
    public function price(): BelongsTo { return $this->belongsTo(CommercePrice::class, 'price_id'); }
}
