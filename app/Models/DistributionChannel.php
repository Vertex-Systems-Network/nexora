<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class DistributionChannel extends Model
{ use BelongsToTenant;
    protected $table = 'nx_distribution_channels';
    protected $guarded = [];
    protected function casts(): array { return ['enabled'=>'boolean','settings'=>'encrypted:array','metadata'=>'array']; }
}
