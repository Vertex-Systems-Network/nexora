<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RuntimeMetric extends Model
{
    public $timestamps = false;
    protected $table = 'nx_runtime_metrics';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'tags' => 'array',
            'observed_at' => 'datetime',
        ];
    }
}
