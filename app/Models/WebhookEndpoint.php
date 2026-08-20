<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WebhookEndpoint extends Model
{ use BelongsToTenant;
    protected $table = 'nx_webhook_endpoints';
    protected $guarded = [];
    protected $hidden = ['secret', 'previous_secret'];
    protected function casts(): array { return ['secret'=>'encrypted','previous_secret'=>'encrypted','allowed_ips'=>'array','enabled'=>'boolean','last_received_at'=>'datetime','rotated_at'=>'datetime','previous_secret_valid_until'=>'datetime']; }
    public function receipts(): HasMany { return $this->hasMany(WebhookReceipt::class); }
}
