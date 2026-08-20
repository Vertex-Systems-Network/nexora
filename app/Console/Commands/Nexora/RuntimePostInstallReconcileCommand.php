<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\RuntimePostInstallHandoff;
use Illuminate\Console\Command;

final class RuntimePostInstallReconcileCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    protected $signature = 'nexora:runtime:post-install-reconcile
        {--confirm= : Must be RECONCILE to record a new sealed handoff receipt}';

    protected $description = 'Safely finalize the one-time installer runtime identity transition, then record a sealed post-install handoff receipt.';

    public function handle(RuntimePostInstallHandoff $handoff): int
    {
        if ((string) $this->option('confirm') !== 'RECONCILE') {
            $this->error('Refusing to mutate handoff evidence without --confirm=RECONCILE.');

            return self::FAILURE;
        }

        try {
            $receipt = $handoff->reconcileReceipt();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode(
            $receipt,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        return self::SUCCESS;
    }
}
