<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class RuntimeClearCommand extends Command
{
    protected $signature = 'nexora:runtime:clear';
    protected $description = 'Remove the compiled Nexora runtime metadata cache.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('nexora.modules.cache_path');
        if ($files->exists($path)) {
            $files->delete($path);
        }
        $this->components->info('Nexora runtime cache cleared.');

        return self::SUCCESS;
    }
}
