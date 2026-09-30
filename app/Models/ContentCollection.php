<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ContentCollection extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_content_collections';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'metadata' => 'array',
        ];
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'nx_content_collection_documents', 'collection_id', 'document_id')
            ->withPivot(['position', 'data'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
