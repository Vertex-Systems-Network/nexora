<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\AdminNotification;
use App\Models\WebhookDelivery;
use App\Models\WebhookDestination;
use App\Models\WorkflowRun;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Support\Str;
use RuntimeException;

final class WorkflowActionExecutor
{
    public function __construct(private AuditManager $audit) {}

    /** @param array<string,mixed> $action */
    public function execute(WorkflowRun $run, array $action): array
    {
        $type = (string) ($action['type'] ?? '');
        $config = (array) ($action['config'] ?? []);
        return match ($type) {
            'admin.notification' => $this->notification($run, $config),
            'webhook.send' => $this->webhook($run, $config, (string) ($action['key'] ?? 'webhook')),
            'audit.record' => $this->audit($run, $config),
            default => throw new RuntimeException('Unsupported workflow action: '.$type),
        };
    }

    private function notification(WorkflowRun $run, array $config): array
    {
        $notification = AdminNotification::query()->create([
            'user_id'=>(int) ($config['user_id'] ?? 0),
            'type'=>'automation',
            'title'=>$this->render((string) ($config['title'] ?? 'Automation notification'), (array) $run->context),
            'message'=>$this->render((string) ($config['message'] ?? ''), (array) $run->context),
            'action_url'=>filled($config['action_url'] ?? null) ? $this->render((string) $config['action_url'], (array) $run->context) : null,
            'metadata'=>['workflow_run_id'=>$run->id,'workflow_id'=>$run->workflow_id],
        ]);
        return ['notification_id'=>$notification->id];
    }

    private function webhook(WorkflowRun $run, array $config, string $stepKey): array
    {
        $destination = WebhookDestination::query()->whereKey((int) ($config['destination_id'] ?? 0))->where('enabled',true)->first();
        if (! $destination) throw new RuntimeException('Webhook destination is unavailable or disabled.');
        $payload = ['event'=>$run->event?->event_key ?? 'manual','workflow'=>['id'=>$run->workflow_id,'run_id'=>$run->id,'run_uuid'=>$run->uuid],'data'=>(array) $run->context];
        $idempotencyKey = 'workflow:'.$run->uuid.':'.$stepKey.':'.$destination->uuid;
        $delivery = WebhookDelivery::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'uuid' => (string) Str::uuid(),
                'webhook_destination_id' => $destination->id,
                'workflow_run_id' => $run->id,
                'event_key' => $run->event?->event_key ?? 'manual',
                'payload' => $payload,
                'status' => 'queued',
            ],
        );
        if ($delivery->wasRecentlyCreated) DeliverWebhookJob::dispatch($delivery->id)->afterCommit();
        return ['delivery_id' => $delivery->id, 'status' => $delivery->status];
    }

    private function audit(WorkflowRun $run, array $config): array
    {
        $event = trim((string) ($config['event'] ?? 'automation.workflow.action')) ?: 'automation.workflow.action';
        $log = $this->audit->record($event, $run->workflow, ['workflow_run_id'=>$run->id,'context'=>$run->context]);
        return ['audit_log_id'=>$log->id];
    }

    private function render(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', static fn (array $match): string => (string) data_get($context, $match[1], ''), $template) ?? $template;
    }
}
