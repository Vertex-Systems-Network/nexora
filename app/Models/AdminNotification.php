<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AdminNotification extends Model
{
    protected $table = 'nx_admin_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'message', 'action_url', 'read_at', 'metadata'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'metadata' => 'array'];
    }
}
