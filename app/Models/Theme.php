<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Theme extends Model
{
    protected $table = 'nx_themes';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ThemeVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThemeVersion::class, 'theme_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ThemeSetting::class, 'theme_id');
    }
}
