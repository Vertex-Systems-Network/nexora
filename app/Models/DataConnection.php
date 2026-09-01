<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

final class DataConnection extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_data_connections';

    protected $fillable = [
        'tenant_id', 'name', 'provider', 'driver', 'purpose', 'status', 'is_enabled', 'endpoint',
        'database', 'username', 'secret_payload', 'options', 'last_tested_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'options' => 'array',
            'secret_payload' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }
}
