<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use App\Jobs\ExecuteWorkflowRunJob;
use App\Models\AutomationEvent;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

final class AutomationEventBus implements AutomationEventBusContract
{
    public function __construct(
        private ConditionEvaluator $conditions,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function emit(string $eventKey, array $payload = [], ?string $sourceType = null, string|int|null $sourceId = null, ?string $idempotencyKey = null): ?AutomationEvent
    {
        if (! Workflow::query()->where('status', 'active')->where('trigger_key', $eventKey)->exists()) return null;
        $idempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);

        try {
            return $this->process($eventKey, $payload, $sourceType, $sourceId, $idempotencyKey);
        } catch (QueryException $exception) {
            // Two emitters can both observe "missing" before either insert commits. The
            // unique constraint is authoritative; retry the loser against the committed row.
            if ($idempotencyKey !== null && $this->concurrency->isUniqueViolation($exception)) {
                return $this->process($eventKey, $payload, $sourceType, $sourceId, $idempotencyKey);
            }
            throw $exception;
        }
    }

    private function process(string $eventKey, array $payload, ?string $sourceType, string|int|null $sourceId, ?string $idempotencyKey): ?AutomationEvent
    {
        return $this->concurrency->transaction(function () use ($eventKey, $payload, $sourceType, $sourceId, $idempotencyKey): ?AutomationEvent {
            $event = null;
            if ($idempotencyKey !== null) {
                $event = AutomationEvent::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($event?->processed_at !== null) return $event;
            }

            $event ??= AutomationEvent::query()->create([
                'uuid' => (string) Str::uuid(),
                'event_key' => $eventKey,
                'source_type' => $sourceType,
                'source_id' => $sourceId !== null ? (string) $sourceId : null,
                'idempotency_key' => $idempotencyKey,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);

            // The event row is locked for the whole fan-out. If the transaction fails,
            // both WorkflowRun creation and processed_at roll back together; a retry can
            // safely resume instead of leaving a "processed" event with missing runs.
            $workflows = Workflow::query()->where('status', 'active')->where('trigger_key', $eventKey)->get();
            foreach ($workflows as $workflow) {
                if (! $this->triggerMatches($workflow, $payload) || ! $this->conditions->passes((array) $workflow->conditions, $payload)) continue;
                $run = WorkflowRun::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'workflow_id' => $workflow->id,
                    'automation_event_id' => $event->id,
                    'status' => 'queued',
                    'context' => $payload,
                ]);
                ExecuteWorkflowRunJob::dispatch($run->id)->afterCommit();
            }

            $event->forceFill(['processed_at' => now()])->save();
            return $event->refresh();
        });
    }

    private function triggerMatches(Workflow $workflow, array $payload): bool
    {
        if ($workflow->trigger_key !== 'webhook.inbound') return true;
        $expected = (int) data_get((array) $workflow->trigger_config, 'endpoint_id', 0);
        return $expected > 0 && $expected === (int) data_get($payload, 'webhook.endpoint_id', 0);
    }

    private function normalizeIdempotencyKey(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 190);
    }
}
