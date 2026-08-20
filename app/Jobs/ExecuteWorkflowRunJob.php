<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowStepRun;
use App\Nexora\Automation\Services\WorkflowActionExecutor;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ExecuteWorkflowRunJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;
    public bool $failOnTimeout = true;
    public function backoff(): array { return [10, 60, 300]; }
    public function __construct(public int $runId) {}

    public function handle(
        WorkflowActionExecutor $executor,
        TenantExecutionScope $tenantScope,
        ConcurrencyGuard $concurrency,
    ): void {
        $tenantId = WorkflowRun::query()
            ->withoutGlobalScope('nexora_tenant')
            ->whereKey($this->runId)
            ->value('tenant_id');

        $tenantScope->runRequired(
            is_string($tenantId) ? $tenantId : null,
            "workflow run {$this->runId}",
            fn () => $this->execute($executor, $concurrency),
        );
    }

    private function execute(
        WorkflowActionExecutor $executor,
        ConcurrencyGuard $concurrency,
    ): void {
        $claim = $this->claimRun($concurrency);
        if (! $claim) {
            return;
        }

        $run = WorkflowRun::query()->with(['workflow', 'event'])->findOrFail($this->runId);
        if (! $run->workflow || $run->workflow->status !== 'active') {
            $run->forceFill([
                'status' => 'skipped',
                'completed_at' => now(),
                'error' => 'Workflow is no longer active.',
            ])->save();

            return;
        }

        $outputs = [];

        try {
            foreach ((array) $run->workflow->actions as $index => $action) {
                $key = (string) ($action['key'] ?? 'step-'.($index + 1));
                $step = $this->claimStep($run, $key, (array) $action, $concurrency);

                if ($step === null) {
                    $existing = WorkflowStepRun::query()
                        ->where('workflow_run_id', $run->id)
                        ->where('step_key', $key)
                        ->first();

                    if ($existing?->status === 'succeeded') {
                        $outputs[$key] = $existing->output;
                        continue;
                    }

                    // A live claimant owns this step. Returning avoids duplicate side effects.
                    return;
                }

                $result = $executor->execute($run, (array) $action);
                WorkflowStepRun::query()
                    ->whereKey($step->id)
                    ->where('status', 'running')
                    ->update([
                        'status' => 'succeeded',
                        'output' => $result,
                        'completed_at' => now(),
                        'error' => null,
                        'updated_at' => now(),
                    ]);

                $outputs[$key] = $result;
            }

            $completed = WorkflowRun::query()
                ->whereKey($run->id)
                ->where('status', 'running')
                ->update([
                    'status' => 'succeeded',
                    'output' => $outputs,
                    'completed_at' => now(),
                    'error' => null,
                    'updated_at' => now(),
                ]);

            if ($completed === 1) {
                Workflow::query()
                    ->whereKey($run->workflow_id)
                    ->increment('run_count', 1, ['last_run_at' => now()]);
            }
        } catch (Throwable $exception) {
            if (isset($step)) {
                WorkflowStepRun::query()
                    ->whereKey($step->id)
                    ->where('status', 'running')
                    ->update([
                        'status' => 'failed',
                        'error' => mb_substr($exception->getMessage(), 0, 4000),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            WorkflowRun::query()
                ->whereKey($run->id)
                ->where('status', 'running')
                ->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 4000),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            throw $exception;
        }
    }

    private function claimRun(ConcurrencyGuard $concurrency): bool
    {
        return $concurrency->transaction(function (): bool {
            $run = WorkflowRun::query()->whereKey($this->runId)->lockForUpdate()->first();
            if (! $run || in_array($run->status, ['succeeded', 'skipped'], true)) return false;

            $staleBefore = now()->subSeconds((int) config('nexora-concurrency.workflow_claim_ttl_seconds', 240));
            if ($run->status === 'running' && $run->updated_at?->greaterThan($staleBefore)) return false;

            $run->forceFill([
                'status' => 'running',
                'attempt' => ((int) $run->attempt) + 1,
                'started_at' => $run->started_at ?? now(),
                'completed_at' => null,
                'error' => null,
            ])->save();
            return true;
        });
    }

    /** @param array<string,mixed> $action */
    private function claimStep(WorkflowRun $run, string $key, array $action, ConcurrencyGuard $concurrency): ?WorkflowStepRun
    {
        return $concurrency->transaction(function () use ($run, $key, $action): ?WorkflowStepRun {
            $step = WorkflowStepRun::query()->firstOrCreate(
                ['workflow_run_id' => $run->id, 'step_key' => $key],
                ['action_type' => (string) ($action['type'] ?? ''), 'status' => 'queued', 'input' => $action],
            );
            $step = WorkflowStepRun::query()->whereKey($step->id)->lockForUpdate()->firstOrFail();
            if ($step->status === 'succeeded') return null;

            $staleBefore = now()->subSeconds((int) config('nexora-concurrency.workflow_claim_ttl_seconds', 240));
            if ($step->status === 'running' && $step->updated_at?->greaterThan($staleBefore)) return null;

            $step->forceFill([
                'status' => 'running',
                'attempt' => ((int) $step->attempt) + 1,
                'started_at' => $step->started_at ?? now(),
                'completed_at' => null,
                'error' => null,
            ])->save();
            return $step->refresh();
        });
    }
}
