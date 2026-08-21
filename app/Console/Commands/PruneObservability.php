<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Nexora\Observability\Services\ObservabilityRetentionService;
use Illuminate\Console\Command;

final class PruneObservability extends Command
{
    protected $signature = 'nexora:observability:prune';
    protected $description = 'Prune retained Nexora tenant audit and operational incident telemetry within bounded retention policies.';

    public function handle(ObservabilityRetentionService $retention): int
    {
        $deleted = $retention->prune();
        $this->info(sprintf(
            'Pruned %d audit log(s) and %d operational incident(s).',
            $deleted['audit_logs'],
            $deleted['incidents'],
        ));
        $this->line('Runtime metrics remain owned by the existing nexora:runtime:prune policy.');

        return self::SUCCESS;
    }
}
