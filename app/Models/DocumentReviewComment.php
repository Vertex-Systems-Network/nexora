<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DocumentReviewComment extends Model
{
    protected $table = 'nx_document_review_comments';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function revision(): BelongsTo { return $this->belongsTo(DocumentRevision::class, 'revision_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
