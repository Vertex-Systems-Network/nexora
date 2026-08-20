<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TaxonomyTerm extends Model
{ use BelongsToTenant;
    protected $table = 'nx_taxonomy_terms';
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'nx_document_terms', 'term_id', 'document_id')
            ->withPivot(['is_primary', 'position'])->withTimestamps();
    }
}
