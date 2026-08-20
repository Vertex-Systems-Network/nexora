<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceProduct extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table = 'nx_commerce_products';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array','published_at'=>'datetime']; }
    public function prices(): HasMany { return $this->hasMany(CommercePrice::class, 'product_id'); }
}
