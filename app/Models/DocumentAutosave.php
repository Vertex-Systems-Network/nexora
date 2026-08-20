<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DocumentAutosave extends Model
{
    protected $table = 'nx_document_autosaves';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'metadata' => 'array', 'saved_at' => 'datetime'];
    }

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
