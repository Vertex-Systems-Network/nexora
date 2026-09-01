<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContentMigrationRun extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'nx_content_migration_runs';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'source_bytes' => 'integer',
            'cursor' => 'integer',
            'processed_items' => 'integer',
            'imported_items' => 'integer',
            'skipped_items' => 'integer',
            'failed_items' => 'integer',
            'options' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContentMigrationItem::class, 'migration_run_id');
    }
}
