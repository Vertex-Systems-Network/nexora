<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CommerceBillingEvent extends Model
{
    use HasUuids;
    protected $table = 'nx_commerce_billing_events';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected function casts(): array { return ['payload'=>'array','occurred_at'=>'datetime','created_at'=>'datetime']; }
}
