<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplyChainArtifact extends Model
{
    use HasUuids;
    protected $table = 'nx_supply_chain_artifacts';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['sbom'=>'array','provenance'=>'array','verified_at'=>'datetime']; }
    public function package(): BelongsTo { return $this->belongsTo(QuarantinePackage::class, 'quarantine_package_id'); }
    public function scan(): BelongsTo { return $this->belongsTo(SecurityScan::class, 'scan_id'); }
    public function publisher(): BelongsTo { return $this->belongsTo(TrustedPublisher::class, 'publisher_id'); }
    public function components(): HasMany { return $this->hasMany(SupplyChainComponent::class, 'artifact_id'); }
    public function attestations(): HasMany { return $this->hasMany(SupplyChainAttestation::class, 'artifact_id'); }
}
