<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class ThemePreviewToken extends Model
{
    use HasUuids;

    protected $table = 'nx_theme_preview_tokens';
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
