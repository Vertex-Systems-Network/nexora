<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SystemHealth extends Model
{
    protected $table = 'nx_system_health';

    protected $fillable = ['key', 'status', 'message', 'details', 'checked_at'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
