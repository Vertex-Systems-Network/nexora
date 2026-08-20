<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ThemeVersion extends Model
{
    protected $table = 'nx_theme_versions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }
}
