<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommerceCurrency extends Model
{
    protected $table = 'nx_commerce_currencies';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected function casts(): array { return ['enabled'=>'boolean','is_default'=>'boolean']; }
}
