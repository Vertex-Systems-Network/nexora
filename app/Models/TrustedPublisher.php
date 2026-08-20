<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TrustedPublisher extends Model
{
    use HasUuids;
    protected $table = 'nx_trusted_publishers';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array']; }
    public function artifacts(): HasMany { return $this->hasMany(SupplyChainArtifact::class, 'publisher_id'); }
}
