<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class DocumentRevision extends Model
{
    public $timestamps = false;
    protected $table = 'nx_document_revisions';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'metadata' => 'array', 'created_at' => 'datetime', 'schema_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Document revisions are immutable.'));
        static::deleting(static fn (): never => throw new LogicException('Document revisions cannot be deleted individually.'));
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
