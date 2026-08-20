<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookReceipt extends Model
{ use BelongsToTenant;
    protected $table = 'nx_webhook_receipts';
    protected $guarded = [];
    protected function casts(): array { return ['headers'=>'array','payload'=>'array','received_at'=>'datetime']; }
    public function endpoint(): BelongsTo { return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id'); }
}
