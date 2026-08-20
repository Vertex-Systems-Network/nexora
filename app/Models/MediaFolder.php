<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MediaFolder extends Model
{ use BelongsToTenant;
    protected $table = 'nx_media_folders';
    protected $guarded = [];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
    public function assets(): HasMany { return $this->hasMany(MediaAsset::class, 'folder_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
