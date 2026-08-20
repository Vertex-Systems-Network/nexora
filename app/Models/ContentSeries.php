<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ContentSeries extends Model
{ use BelongsToTenant;
    protected $table = 'nx_content_series';
    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'nx_content_series_items', 'series_id', 'document_id')
            ->withPivot('position')->withTimestamps()->orderByPivot('position');
    }
}
