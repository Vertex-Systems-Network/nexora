<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RuntimeNode extends Model
{
    use HasUuids;

    protected $table = 'nx_runtime_nodes';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'metadata' => 'array',
            'last_heartbeat_at' => 'datetime',
        ];
    }
}
