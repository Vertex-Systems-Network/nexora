<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommerceRefund extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_refunds';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array','processed_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(CommerceOrder::class, 'order_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(CommercePaymentTransaction::class, 'payment_transaction_id'); }
}
