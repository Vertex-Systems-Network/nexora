<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommercePaymentTransaction extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'nx_commerce_payment_transactions';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['metadata'=>'array','processed_at'=>'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(CommerceOrder::class, 'order_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(CommerceInvoice::class, 'invoice_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class, 'customer_id'); }
    public function refunds(): HasMany { return $this->hasMany(CommerceRefund::class, 'payment_transaction_id'); }
}
