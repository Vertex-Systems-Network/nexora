<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudioRevision extends Model
{
    public $timestamps = false;
    protected $table = 'nx_studio_revisions';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function canvas(): BelongsTo { return $this->belongsTo(StudioCanvas::class, 'canvas_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    protected static function booted(): void
    {
        static::updating(static function (): void { throw new \LogicException('Studio revisions are immutable.'); });
        static::deleting(static function (): void { throw new \LogicException('Studio revisions cannot be deleted individually.'); });
    }
}
