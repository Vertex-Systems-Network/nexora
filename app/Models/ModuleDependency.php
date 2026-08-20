<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModuleDependency extends Model
{
    protected $table = 'nx_module_dependencies';

    protected $fillable = ['module_id', 'dependency_identifier', 'version_constraint', 'is_optional'];

    protected function casts(): array
    {
        return ['is_optional' => 'boolean'];
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
