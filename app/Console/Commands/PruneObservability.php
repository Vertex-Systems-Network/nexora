<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Nexora\Observability\Services\ObservabilityRetentionService;
use Illuminate\Console\Command;

final class PruneObservability extends Command
{
    protected $signature = 'nexora:observability:prune';
    protected $description = 'Prune retained Nexora audit, incident and runtime metric telemetry within bounded retention policies.';

    public function handle(ObservabilityRetentionService $retention): int
    {
        $deleted = $retention->prune();
        $this->info(sprintf(
            'Pruned %d audit log(s), %d incident(s) and %d runtime metric row(s).',
            $deleted['audit_logs'],
            $deleted['incidents'],
            $deleted['runtime_metrics'],
        ));

        return self::SUCCESS;
    }
}
