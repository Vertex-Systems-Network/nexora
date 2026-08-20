<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ArticleMetadata extends Model
{ use BelongsToTenant;
    protected $table = 'nx_article_metadata';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'featured_until' => 'datetime', 'is_featured' => 'boolean', 'allow_comments' => 'boolean', 'is_sponsored' => 'boolean', 'metadata' => 'array'];
    }
    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function heroMedia(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'hero_media_id'); }
}
