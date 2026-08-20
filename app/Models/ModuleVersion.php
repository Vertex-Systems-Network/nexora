<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModuleVersion extends Model
{
    protected $table = 'nx_module_versions';

    protected $fillable = ['module_id', 'version', 'checksum', 'installed_at', 'metadata'];

    protected function casts(): array
    {
        return ['installed_at' => 'datetime', 'metadata' => 'array'];
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}
