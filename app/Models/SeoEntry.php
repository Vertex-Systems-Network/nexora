<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SeoEntry extends Model
{ use BelongsToTenant;
    protected $table = 'nx_seo_entries';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'robots_directives' => 'array',
            'schema_overrides' => 'array',
            'social' => 'array',
            'sitemap_include' => 'boolean',
            'last_indexed_at' => 'datetime',
        ];
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
