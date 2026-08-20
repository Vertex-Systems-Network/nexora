<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Module extends Model
{
    protected $table = 'nx_modules';

    protected $fillable = [
        'identifier', 'name', 'class', 'version', 'status', 'load_order', 'trust_level',
        'manifest_hash', 'enabled_at', 'last_booted_at', 'is_core', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'metadata' => 'array',
            'enabled_at' => 'datetime',
            'last_booted_at' => 'datetime',
            'load_order' => 'integer',
        ];
    }

    /** @return HasMany<ModuleVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ModuleVersion::class, 'module_id');
    }

    /** @return HasMany<ModuleDependency, $this> */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ModuleDependency::class, 'module_id');
    }

    /** @return BelongsToMany<Capability, $this> */
    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(Capability::class, 'nx_module_capabilities')->withPivot('mode')->withTimestamps();
    }
}
