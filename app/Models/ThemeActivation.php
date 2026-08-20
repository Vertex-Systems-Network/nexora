<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class ThemeActivation extends Model
{ use BelongsToTenant;
    protected $table = 'nx_theme_activations';
    protected $guarded = [];
}
