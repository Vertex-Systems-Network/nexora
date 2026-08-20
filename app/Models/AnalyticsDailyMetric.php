<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class AnalyticsDailyMetric extends Model
{ use BelongsToTenant;
    protected $table = 'nx_analytics_daily_metrics';
    protected $guarded = [];
    protected function casts(): array { return ['metric_date'=>'date']; }
}
