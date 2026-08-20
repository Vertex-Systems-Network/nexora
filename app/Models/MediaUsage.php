<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MediaUsage extends Model
{
    protected $table = 'nx_media_usages';
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function asset(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'asset_id'); }
}
