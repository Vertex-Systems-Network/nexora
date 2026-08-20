<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExtensionVersion extends Model
{
    use HasUuids;
    protected $table = 'nx_extension_versions';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['manifest'=>'array','schema_compatible_rollback'=>'boolean','installed_at'=>'datetime','activated_at'=>'datetime','migrations_applied_at'=>'datetime']; }
    public function extension(): BelongsTo { return $this->belongsTo(Extension::class, 'extension_id'); }
    public function artifact(): BelongsTo { return $this->belongsTo(SupplyChainArtifact::class, 'artifact_id'); }
    public function dependencies(): HasMany { return $this->hasMany(ExtensionDependency::class, 'extension_version_id'); }
}
