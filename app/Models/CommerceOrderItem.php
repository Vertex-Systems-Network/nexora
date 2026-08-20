<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommerceOrderItem extends Model
{
    use HasUuids;
    protected $table = 'nx_commerce_order_items';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array']; }
    public function order(): BelongsTo { return $this->belongsTo(CommerceOrder::class, 'order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(CommerceProduct::class, 'product_id'); }
    public function price(): BelongsTo { return $this->belongsTo(CommercePrice::class, 'price_id'); }
}
