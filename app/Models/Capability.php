<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Capability extends Model
{
    protected $table = 'nx_capabilities';

    protected $fillable = ['slug', 'name', 'group', 'risk_level', 'description'];

    /** @return BelongsToMany<Module, $this> */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'nx_module_capabilities')->withPivot('mode')->withTimestamps();
    }
}
