<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Nexora\Automation\Services\WebhookDeliveryService;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 8;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    public function backoff(): array { return [30, 120, 600, 1800]; }
    public function __construct(public int $deliveryId) {}

    public function handle(WebhookDeliveryService $service, TenantExecutionScope $tenantScope): void
    {
        $tenantId = WebhookDelivery::query()
            ->withoutGlobalScope('nexora_tenant')
            ->whereKey($this->deliveryId)
            ->value('tenant_id');

        $tenantScope->runRequired(
            is_string($tenantId) ? $tenantId : null,
            "webhook delivery {$this->deliveryId}",
            function () use ($service): void {
                $delivery = WebhookDelivery::query()->findOrFail($this->deliveryId);

                if ($delivery->status !== 'delivered') {
                    $service->deliver($delivery);
                }
            },
        );
    }

    public function failed(?Throwable $exception): void
    {
        WebhookDelivery::query()->whereKey($this->deliveryId)->where('status', '!=', 'delivered')->update([
            'status' => 'failed',
            'error' => mb_substr($exception?->getMessage() ?? 'Webhook delivery failed after retries.', 0, 4000),
            'updated_at' => now(),
        ]);
    }
}
