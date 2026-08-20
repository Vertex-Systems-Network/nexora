<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MediaAsset extends Model
{ use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'nx_media_assets';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer', 'width' => 'integer', 'height' => 'integer', 'duration_ms' => 'integer',
            'focal_x' => 'decimal:2', 'focal_y' => 'decimal:2', 'variants' => 'array', 'metadata' => 'array',
        ];
    }

    public function folder(): BelongsTo { return $this->belongsTo(MediaFolder::class, 'folder_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function collections(): BelongsToMany { return $this->belongsToMany(MediaCollection::class, 'nx_media_collection_items', 'asset_id', 'collection_id')->withPivot('position')->withTimestamps(); }
    public function usages(): HasMany { return $this->hasMany(MediaUsage::class, 'asset_id'); }

    public function publicUrl(): ?string
    {
        if ($this->visibility !== 'public' || $this->trashed()) return null;
        return url('/media/'.$this->uuid);
    }
}
