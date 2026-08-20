<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplyChainComponent extends Model
{
    protected $table = 'nx_supply_chain_components';
    protected $guarded = [];
    protected function casts(): array { return ['is_direct'=>'boolean','licenses'=>'array','hashes'=>'array','metadata'=>'array']; }
    public function artifact(): BelongsTo { return $this->belongsTo(SupplyChainArtifact::class, 'artifact_id'); }
}
