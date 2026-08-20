<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceInvoice extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_invoices';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array','issued_at'=>'datetime','due_at'=>'datetime','paid_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(CommerceOrder::class, 'order_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class, 'customer_id'); }
    public function transactions(): HasMany { return $this->hasMany(CommercePaymentTransaction::class, 'invoice_id'); }
}
