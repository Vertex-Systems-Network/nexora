<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplyChainAttestation extends Model
{
    use HasUuids;
    protected $table = 'nx_supply_chain_attestations';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['verified'=>'boolean','payload'=>'array','verified_at'=>'datetime']; }
    public function artifact(): BelongsTo { return $this->belongsTo(SupplyChainArtifact::class, 'artifact_id'); }
}
