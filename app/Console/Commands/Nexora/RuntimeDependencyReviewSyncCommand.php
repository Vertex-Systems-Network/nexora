<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\DependencyReviewSynchronizer;
use Illuminate\Console\Command;
use Throwable;

final class RuntimeDependencyReviewSyncCommand extends Command
{
    protected $signature = 'nexora:runtime:dependency-review-sync
        {--operator= : Real operator name recorded in the provenance receipt}
        {--confirm= : Must equal SYNC}';

    protected $description = 'Promote bootstrap dependency provenance to current reviewed-lock provenance without changing deployment generation.';

    public function handle(DependencyReviewSynchronizer $synchronizer): int
    {
        if ((string) $this->option('confirm') !== 'SYNC') {
            $this->error('Explicit --confirm=SYNC is required.');
            return self::INVALID;
        }

        try {
            $result = $synchronizer->sync((string) $this->option('operator'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Nexora dependency review provenance synchronized.');
        $this->line(json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        return self::SUCCESS;
    }
}
