<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class MediaCollection extends Model
{ use BelongsToTenant;
    protected $table = 'nx_media_collections';
    protected $guarded = [];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assets(): BelongsToMany { return $this->belongsToMany(MediaAsset::class, 'nx_media_collection_items', 'collection_id', 'asset_id')->withPivot('position')->withTimestamps()->orderByPivot('position'); }
}
