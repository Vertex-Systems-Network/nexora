<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Contracts\NexoraKernelContract;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class RuntimeCacheCommand extends Command
{
    protected $signature = 'nexora:runtime:cache';
    protected $description = 'Compile a deterministic Nexora runtime metadata snapshot.';

    public function handle(NexoraKernelContract $kernel, Filesystem $files): int
    {
        $path = (string) config('nexora.modules.cache_path');
        $files->ensureDirectoryExists(dirname($path));
        $payload = '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($kernel->snapshot(), true).';'.PHP_EOL;
        $files->put($path, $payload);
        $this->components->info("Nexora runtime cache written to {$path}.");

        return self::SUCCESS;
    }
}
