<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceOrder extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_orders';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['billing_address'=>'array','shipping_address'=>'array','metadata'=>'array','placed_at'=>'datetime','completed_at'=>'datetime','cancelled_at'=>'datetime']; }
    public function customer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class, 'customer_id'); }
    public function items(): HasMany { return $this->hasMany(CommerceOrderItem::class, 'order_id'); }
    public function invoices(): HasMany { return $this->hasMany(CommerceInvoice::class, 'order_id'); }
    public function transactions(): HasMany { return $this->hasMany(CommercePaymentTransaction::class, 'order_id'); }
    public function refunds(): HasMany { return $this->hasMany(CommerceRefund::class, 'order_id'); }
}
