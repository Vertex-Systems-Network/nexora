<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class SearchIndexEntry extends Model
{ use BelongsToTenant;
    protected $table = 'nx_search_index';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at'=>'datetime','indexed_at'=>'datetime'];
    }
}
