<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Extension extends Model
{
    use HasUuids;
    protected $table = 'nx_extensions';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';
    protected function casts(): array { return ['metadata'=>'array','installed_at'=>'datetime','enabled_at'=>'datetime','disabled_at'=>'datetime','uninstalled_at'=>'datetime']; }
    public function versions(): HasMany { return $this->hasMany(ExtensionVersion::class, 'extension_id'); }
    public function grants(): HasMany { return $this->hasMany(ExtensionCapabilityGrant::class, 'extension_id'); }
    public function events(): HasMany { return $this->hasMany(ExtensionLifecycleEvent::class, 'extension_id'); }
    public function publisher(): BelongsTo { return $this->belongsTo(TrustedPublisher::class, 'publisher_id'); }
}
