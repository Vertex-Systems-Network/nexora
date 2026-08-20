<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NewsletterCampaign extends Model
{ use BelongsToTenant;
    protected $table = 'nx_newsletter_campaigns';
    protected $guarded = [];
    protected function casts(): array { return ['scheduled_at'=>'datetime','sent_at'=>'datetime','metadata'=>'array']; }
    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function list(): BelongsTo { return $this->belongsTo(NewsletterList::class, 'list_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function deliveries(): HasMany { return $this->hasMany(NewsletterDelivery::class, 'campaign_id'); }
}
