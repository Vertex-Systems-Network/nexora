<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StudioCanvas extends Model
{ use BelongsToTenant;
    protected $table = 'nx_studio_canvases';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'metadata' => 'array',
            'schema_version' => 'integer',
            'lock_version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function theme(): BelongsTo { return $this->belongsTo(Theme::class); }
    public function revisions(): HasMany { return $this->hasMany(StudioRevision::class, 'canvas_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
