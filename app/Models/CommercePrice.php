<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommercePrice extends Model
{
    use HasUuids;
    protected $table = 'nx_commerce_prices';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime','metadata'=>'array']; }
    public function product(): BelongsTo { return $this->belongsTo(CommerceProduct::class, 'product_id'); }
    public function currencyRecord(): BelongsTo { return $this->belongsTo(CommerceCurrency::class, 'currency', 'code'); }
}
