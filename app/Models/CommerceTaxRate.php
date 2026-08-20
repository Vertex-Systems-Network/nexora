<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CommerceTaxRate extends Model
{
    use HasUuids;
    protected $table = 'nx_commerce_tax_rates';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['inclusive'=>'boolean','active'=>'boolean','metadata'=>'array']; }
}
