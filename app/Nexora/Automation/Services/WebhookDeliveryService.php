<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use App\Models\WebhookDelivery;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use RuntimeException;

final readonly class WebhookDeliveryService
{
    public function __construct(
        private WebhookSigner $signer,
        private WebhookUrlPolicy $policy,
        private ApprovedHttpClient $http,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function deliver(WebhookDelivery $delivery): void
    {
        $claimed = $this->claim($delivery->id);
        if (! $claimed) return;

        $destination = $claimed->destination;
        if (! $destination || ! $destination->enabled) {
            $this->releaseFailure($claimed->id, 'Webhook destination is disabled or unavailable.');
            throw new RuntimeException('Webhook destination is disabled or unavailable.');
        }

        $this->policy->assertAllowed($destination->url, app()->environment('production'));
        $body = json_encode($claimed->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $headers = array_merge((array) $destination->headers, [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nexora-Webhook/0.27',
            'X-Nexora-Event' => $claimed->event_key,
            'X-Nexora-Delivery' => $claimed->uuid,
            'X-Nexora-Timestamp' => $timestamp,
            'X-Nexora-Signature' => $this->signer->signature((string) $destination->secret, $timestamp, $body),
            'Idempotency-Key' => $claimed->idempotency_key,
        ]);

        $response = $this->http->external($destination->url)->withHeaders($headers)
            ->timeout(max(2, min(30, (int) $destination->timeout_seconds)))
            ->withBody($body, 'application/json')
            ->post($destination->url);

        if (! $response->successful()) {
            WebhookDelivery::query()->whereKey($claimed->id)->where('status', 'sending')->update([
                'status' => 'queued',
                'response_status' => $response->status(),
                'response_excerpt' => mb_substr($response->body(), 0, 2000),
                'error' => 'Webhook responded with HTTP '.$response->status().'.',
                'updated_at' => now(),
            ]);
            throw new RuntimeException('Webhook delivery failed with HTTP '.$response->status().'.');
        }

        WebhookDelivery::query()->whereKey($claimed->id)->where('status', 'sending')->update([
            'status' => 'delivered',
            'response_status' => $response->status(),
            'response_excerpt' => mb_substr($response->body(), 0, 2000),
            'delivered_at' => now(),
            'error' => null,
            'updated_at' => now(),
        ]);
        $destination->forceFill(['last_delivered_at' => now()])->save();
    }

    private function claim(int $deliveryId): ?WebhookDelivery
    {
        return $this->concurrency->transaction(function () use ($deliveryId): ?WebhookDelivery {
            $delivery = WebhookDelivery::query()->with('destination')->whereKey($deliveryId)->lockForUpdate()->first();
            if (! $delivery || in_array($delivery->status, ['delivered', 'failed'], true)) return null;

            $maxAttempts = max(1, min(8, (int) ($delivery->destination?->max_attempts ?? 5)));
            if ((int) $delivery->attempt_count >= $maxAttempts) {
                $delivery->forceFill(['status' => 'failed', 'error' => 'Configured webhook retry limit reached.'])->save();
                return null;
            }

            $staleBefore = now()->subSeconds((int) config('nexora-concurrency.webhook_claim_ttl_seconds', 90));
            if ($delivery->status === 'sending' && $delivery->last_attempt_at?->greaterThan($staleBefore)) return null;
            if (! in_array($delivery->status, ['queued', 'sending'], true)) return null;

            $delivery->forceFill([
                'status' => 'sending',
                'attempt_count' => ((int) $delivery->attempt_count) + 1,
                'last_attempt_at' => now(),
                'error' => null,
            ])->save();
            return $delivery->refresh()->load('destination');
        });
    }

    private function releaseFailure(int $deliveryId, string $message): void
    {
        WebhookDelivery::query()->whereKey($deliveryId)->where('status', 'sending')->update([
            'status' => 'queued',
            'error' => mb_substr($message, 0, 4000),
            'updated_at' => now(),
        ]);
    }
}
