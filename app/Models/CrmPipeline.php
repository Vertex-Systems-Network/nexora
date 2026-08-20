<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrmPipeline extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'nx_crm_pipelines';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CrmPipelineStage::class, 'pipeline_id')
            ->orderBy('position');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'pipeline_id');
    }
}
