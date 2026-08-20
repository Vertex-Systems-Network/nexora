<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class SearchQueryLog extends Model
{ use BelongsToTenant;
    protected $table = 'nx_search_query_logs';
    protected $guarded = [];
    protected function casts(): array { return ['searched_at'=>'datetime']; }
}
