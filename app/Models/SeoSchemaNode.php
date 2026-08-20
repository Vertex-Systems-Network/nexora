<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

final class SeoSchemaNode extends Model
{ use BelongsToTenant;
    protected $table = 'nx_seo_schema_nodes';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'priority' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
