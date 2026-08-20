<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CommercePaymentProviderConfig extends Model
{
    use HasUuids;
    protected $table = 'nx_commerce_payment_provider_configs';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['enabled'=>'boolean','configuration'=>'array','secret_refs'=>'array','last_health_checked_at'=>'datetime']; }
}
