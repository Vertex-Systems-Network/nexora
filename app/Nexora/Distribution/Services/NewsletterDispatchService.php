<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Jobs\SendNewsletterDelivery;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use RuntimeException;

final readonly class NewsletterDispatchService
{
    public function __construct(private ConcurrencyGuard $concurrency) {}

    public function queue(NewsletterCampaign $campaign): int
    {
        $campaign->load('list');
        if (! $campaign->list_id || ! $campaign->list) throw new RuntimeException('A campaign requires an available audience list before it can be sent.');

        $subscriberIds = $campaign->list->subscribers()
            ->where('nx_newsletter_subscribers.status', 'active')
            ->wherePivot('status', 'subscribed')
            ->pluck('nx_newsletter_subscribers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $result = $this->concurrency->transaction(function () use ($campaign, $subscriberIds): array {
            $locked = NewsletterCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (in_array($locked->status, ['sending', 'sent'], true)) {
                return ['claimed' => false, 'subscriber_ids' => []];
            }
            if (! in_array($locked->status, ['draft', 'scheduled'], true)) {
                throw new RuntimeException('Only draft or scheduled campaigns can be queued.');
            }

            $locked->forceFill(['status' => $subscriberIds === [] ? 'sent' : 'sending'])->save();
            foreach ($subscriberIds as $subscriberId) {
                NewsletterDelivery::query()->firstOrCreate(
                    ['campaign_id' => $locked->id, 'subscriber_id' => $subscriberId],
                    ['status' => 'queued'],
                );
            }

            if ($subscriberIds === []) {
                $locked->forceFill([
                    'sent_at' => now(),
                    'metadata' => array_merge((array) $locked->metadata, ['recipient_count' => 0]),
                ])->save();
            }

            return ['claimed' => true, 'subscriber_ids' => $subscriberIds];
        });

        if (! $result['claimed']) return 0;
        foreach ($result['subscriber_ids'] as $subscriberId) {
            SendNewsletterDelivery::dispatch($campaign->id, $subscriberId);
        }

        return count($result['subscriber_ids']);
    }

    public function queueDue(): int
    {
        $campaigns = NewsletterCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $claimed = 0;
        foreach ($campaigns as $campaign) {
            $before = $campaign->status;
            $this->queue($campaign);
            $fresh = $campaign->fresh();
            if ($before === 'scheduled' && $fresh && in_array($fresh->status, ['sending', 'sent'], true)) $claimed++;
        }
        return $claimed;
    }
}
