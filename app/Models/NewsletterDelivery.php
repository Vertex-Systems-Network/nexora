<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsletterDelivery extends Model
{ use BelongsToTenant;
    protected $table = 'nx_newsletter_deliveries';
    protected $guarded = [];
    protected function casts(): array { return ['attempted_at'=>'datetime','sent_at'=>'datetime']; }
    public function campaign(): BelongsTo { return $this->belongsTo(NewsletterCampaign::class, 'campaign_id'); }
    public function subscriber(): BelongsTo { return $this->belongsTo(NewsletterSubscriber::class, 'subscriber_id'); }
}
