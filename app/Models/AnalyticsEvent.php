<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class AnalyticsEvent extends Model
{ use BelongsToTenant;
    protected $table = 'nx_analytics_events';
    protected $guarded = [];
    protected function casts(): array { return ['metadata'=>'array','occurred_at'=>'datetime']; }
}
