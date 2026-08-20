<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use App\Nexora\Themes\Services\DocumentHtmlRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendNewsletterDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;
    public bool $failOnTimeout = true;
    public function backoff(): array { return [30, 120, 600]; }

    public function __construct(public int $campaignId, public int $subscriberId) {}

    public function handle(
        DocumentHtmlRenderer $renderer,
        TenantExecutionScope $tenantScope,
        ConcurrencyGuard $concurrency,
    ): void {
        $tenantId = NewsletterCampaign::query()
            ->withoutGlobalScope('nexora_tenant')
            ->whereKey($this->campaignId)
            ->value('tenant_id');

        $tenantScope->runRequired(
            is_string($tenantId) ? $tenantId : null,
            "newsletter campaign {$this->campaignId}",
            fn () => $this->deliver($renderer, $concurrency),
        );
    }

    private function deliver(
        DocumentHtmlRenderer $renderer,
        ConcurrencyGuard $concurrency,
    ): void {
        $campaign = NewsletterCampaign::query()->with('document')->find($this->campaignId);
        $subscriber = NewsletterSubscriber::query()->find($this->subscriberId);

        $delivery = $this->claim($concurrency);
        if (! $delivery) return;

        if (! $campaign || ! $subscriber || $subscriber->status !== 'active') {
            NewsletterDelivery::query()->whereKey($delivery->id)->where('status', 'sending')->update([
                'status' => 'skipped',
                'attempted_at' => now(),
                'error' => 'Campaign or active subscriber unavailable.',
                'updated_at' => now(),
            ]);
            $this->finishCampaign($concurrency);
            return;
        }

        $body = $campaign->document ? $renderer->render($campaign->document->content) : '<p>'.e((string) $campaign->preview_text).'</p>';
        $unsubscribe = url('/newsletter/unsubscribe/'.$subscriber->unsubscribe_token);
        $html = '<div style="font-family:Arial,sans-serif;line-height:1.6">'.$body.'<hr><p style="font-size:12px;color:#667085">You are receiving this because you subscribed to updates. <a href="'.e($unsubscribe).'">Manage subscription</a>.</p></div>';
        $messageId = (string) $delivery->message_id;

        try {
            Mail::html($html, function (Message $message) use ($campaign, $subscriber, $messageId): void {
                $message->to($subscriber->email, $subscriber->name ?: null)->subject($campaign->subject);
                if ($messageId !== '') {
                    $message->getSymfonyMessage()->getHeaders()->addIdHeader('Message-ID', $messageId);
                }
            });
            NewsletterDelivery::query()->whereKey($delivery->id)->where('status', 'sending')->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error' => null,
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            NewsletterDelivery::query()->whereKey($delivery->id)->where('status', 'sending')->update([
                'status' => 'queued',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'updated_at' => now(),
            ]);
            throw $exception;
        }

        $this->finishCampaign($concurrency);
    }

    public function failed(?Throwable $exception): void
    {
        NewsletterDelivery::query()
            ->where('campaign_id', $this->campaignId)
            ->where('subscriber_id', $this->subscriberId)
            ->whereNotIn('status', ['sent', 'skipped'])
            ->update([
                'status' => 'failed',
                'error' => mb_substr($exception?->getMessage() ?? 'Newsletter delivery failed after retries.', 0, 4000),
                'updated_at' => now(),
            ]);
    }

    private function claim(ConcurrencyGuard $concurrency): ?NewsletterDelivery
    {
        return $concurrency->transaction(function (): ?NewsletterDelivery {
            $delivery = NewsletterDelivery::query()
                ->where('campaign_id', $this->campaignId)
                ->where('subscriber_id', $this->subscriberId)
                ->lockForUpdate()
                ->first();
            if (! $delivery || in_array($delivery->status, ['sent', 'skipped', 'failed'], true)) return null;

            $staleBefore = now()->subSeconds((int) config('nexora-concurrency.newsletter_claim_ttl_seconds', 180));
            if ($delivery->status === 'sending' && $delivery->updated_at?->greaterThan($staleBefore)) return null;
            if (! in_array($delivery->status, ['queued', 'sending'], true)) return null;

            $messageId = (string) ($delivery->message_id ?: $this->messageId());
            $delivery->forceFill([
                'status' => 'sending',
                'message_id' => $messageId,
                'attempted_at' => now(),
                'error' => null,
            ])->save();
            return $delivery->refresh();
        });
    }

    private function finishCampaign(ConcurrencyGuard $concurrency): void
    {
        $concurrency->transaction(function (): void {
            $campaign = NewsletterCampaign::query()->whereKey($this->campaignId)->lockForUpdate()->first();
            if (! $campaign || $campaign->status !== 'sending') return;
            $pending = NewsletterDelivery::query()->where('campaign_id', $campaign->id)->whereIn('status', ['queued', 'sending'])->exists();
            if ($pending) return;

            $campaign->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge((array) $campaign->metadata, [
                    'sent_count' => NewsletterDelivery::query()->where('campaign_id', $campaign->id)->where('status', 'sent')->count(),
                    'failed_count' => NewsletterDelivery::query()->where('campaign_id', $campaign->id)->where('status', 'failed')->count(),
                ]),
            ])->save();
        });
    }

    private function messageId(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'nexora.local';
        return 'newsletter-'.$this->campaignId.'-'.$this->subscriberId.'@'.$host;
    }
}
