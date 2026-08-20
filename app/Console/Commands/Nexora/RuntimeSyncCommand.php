<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use Illuminate\Console\Command;

final class RuntimeSyncCommand extends Command
{
    protected $signature = 'nexora:runtime:sync';
    protected $description = 'Synchronize configured Nexora modules and capabilities to the database.';

    public function handle(RuntimeSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync();
        $this->components->info("Runtime synchronized: {$result['modules']} modules, {$result['capabilities']} capabilities.");

        return self::SUCCESS;
    }
}
