<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NewsletterList extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_newsletter_lists';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(
            NewsletterSubscriber::class,
            'nx_newsletter_list_subscribers',
            'list_id',
            'subscriber_id',
        )
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(NewsletterCampaign::class, 'list_id');
    }
}
