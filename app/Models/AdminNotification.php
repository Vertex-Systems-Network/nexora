<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

final class AdminNotification extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_admin_notifications';

    protected $fillable = ['tenant_id', 'user_id', 'type', 'title', 'message', 'action_url', 'read_at', 'metadata'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'metadata' => 'array'];
    }
}
