<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\DependencyDeploymentReconciler;
use Illuminate\Console\Command;
use Throwable;

final class RuntimeDependencyReconcileCommand extends Command
{
    protected $signature = 'nexora:runtime:dependency-reconcile
        {--operator= : Real operator name recorded in the transition receipt}
        {--confirm= : Must equal RECONCILE}';

    protected $description = 'Reconcile an explicitly reviewed Laravel 13.x dependency refresh with installed deployment identity.';

    public function handle(DependencyDeploymentReconciler $reconciler): int
    {
        if ((string) $this->option('confirm') !== 'RECONCILE') {
            $this->error('Explicit --confirm=RECONCILE is required.');
            return self::INVALID;
        }

        try {
            $result = $reconciler->reconcile((string) $this->option('operator'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Nexora dependency deployment reconciliation completed.');
        $this->line(json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
        $this->warn('Maintenance mode remains enabled. Run your target checks before `php artisan up`.');

        return self::SUCCESS;
    }
}
