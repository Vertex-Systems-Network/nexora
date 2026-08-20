<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SavedView extends Model
{
    protected $table = 'nx_saved_views';

    protected $fillable = ['user_id', 'scope', 'name', 'is_default', 'state'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'state' => 'array'];
    }
}
