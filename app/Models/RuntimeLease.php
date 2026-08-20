<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RuntimeLease extends Model
{
    use HasUuids;

    protected $table = 'nx_runtime_leases';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'heartbeat_at' => 'datetime',
        ];
    }
}
