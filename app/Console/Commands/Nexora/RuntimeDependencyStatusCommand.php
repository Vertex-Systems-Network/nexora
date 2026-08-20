<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\DependencyDeploymentReconciler;
use Illuminate\Console\Command;

final class RuntimeDependencyStatusCommand extends Command
{
    protected $signature = 'nexora:runtime:dependency-status {--json : Emit JSON only}';

    protected $description = 'Inspect Laravel/framework compatibility, dependency runtime identity, review status and deployment drift.';

    public function handle(DependencyDeploymentReconciler $reconciler): int
    {
        $status = $reconciler->inspect();
        $json = json_encode(
            $status,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if ($this->option('json')) {
            $this->line($json);
        } else {
            $this->info('Nexora runtime dependency status: '.strtoupper((string) $status['status']));
            $this->line($json);
        }

        if (($status['status'] ?? null) === 'review-sync-required') {
            $this->warn('Reviewed locks are valid. Run nexora:runtime:dependency-review-sync to promote installation provenance.');
        }

        return in_array(
            $status['status'],
            ['converged', 'dependency-reconcile-required', 'review-sync-required'],
            true,
        ) ? self::SUCCESS : self::FAILURE;
    }
}
