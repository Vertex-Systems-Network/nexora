<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiConnection extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_ai_connections';
    protected $guarded = [];
    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'max_input_chars' => 'integer',
            'max_output_tokens' => 'integer',
            'daily_request_limit' => 'integer',
            'last_health_checked_at' => 'datetime',
        ];
    }

    public function runs(): HasMany { return $this->hasMany(AiGenerationRun::class, 'ai_connection_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
