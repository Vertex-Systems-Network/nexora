<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NewsletterSubscriber extends Model
{ use BelongsToTenant;
    protected $table = 'nx_newsletter_subscribers';
    protected $guarded = [];
    protected function casts(): array { return ['consented_at'=>'datetime','unsubscribed_at'=>'datetime','metadata'=>'array']; }
    public function lists(): BelongsToMany { return $this->belongsToMany(NewsletterList::class, 'nx_newsletter_list_subscribers', 'subscriber_id', 'list_id')->withPivot(['status','subscribed_at','unsubscribed_at'])->withTimestamps(); }
    public function deliveries(): HasMany { return $this->hasMany(NewsletterDelivery::class, 'subscriber_id'); }
}
