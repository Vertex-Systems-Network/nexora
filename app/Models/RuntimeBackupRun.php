<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RuntimeBackupRun extends Model
{
    use HasUuids;

    protected $table = 'nx_runtime_backup_runs';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function restorePlans(): HasMany { return $this->hasMany(RuntimeRestorePlan::class, 'backup_run_id'); }
}
