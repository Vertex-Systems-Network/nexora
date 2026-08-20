<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CrmPipelineStage extends Model
{
    use HasUuids;

    protected $table = 'nx_crm_pipeline_stages';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'stage_id');
    }
}
