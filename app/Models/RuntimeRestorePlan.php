<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RuntimeRestorePlan extends Model
{
    use HasUuids;

    protected $table = 'nx_runtime_restore_plans';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'plan' => 'array',
            'expires_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function backup(): BelongsTo { return $this->belongsTo(RuntimeBackupRun::class, 'backup_run_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
