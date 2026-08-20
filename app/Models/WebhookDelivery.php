<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookDelivery extends Model
{ use BelongsToTenant;
    protected $table = 'nx_webhook_deliveries';
    protected $guarded = [];
    protected function casts(): array { return ['payload'=>'array','last_attempt_at'=>'datetime','delivered_at'=>'datetime']; }
    public function destination(): BelongsTo { return $this->belongsTo(WebhookDestination::class, 'webhook_destination_id'); }
    public function run(): BelongsTo { return $this->belongsTo(WorkflowRun::class, 'workflow_run_id'); }
}
