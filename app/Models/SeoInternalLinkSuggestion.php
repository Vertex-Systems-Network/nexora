<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SeoInternalLinkSuggestion extends Model
{
    protected $table = 'nx_seo_internal_link_suggestions';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['confidence' => 'float'];
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function targetDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'target_document_id');
    }
}
