<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WebhookDestination extends Model
{ use BelongsToTenant;
    protected $table = 'nx_webhook_destinations';
    protected $guarded = [];
    protected $hidden = ['secret'];
    protected function casts(): array { return ['secret'=>'encrypted','headers'=>'array','enabled'=>'boolean','last_delivered_at'=>'datetime','rotated_at'=>'datetime']; }
    public function deliveries(): HasMany { return $this->hasMany(WebhookDelivery::class); }
}
