<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Illuminate\Console\Command;

final class ModuleListCommand extends Command
{
    protected $signature = 'nexora:module:list';
    protected $description = 'List modules registered in the Nexora runtime.';

    public function handle(ModuleRegistryContract $modules): int
    {
        $rows = [];
        $order = array_flip($modules->bootOrder());
        foreach ($modules->manifests() as $identifier => $manifest) {
            $rows[] = [
                $identifier,
                $manifest->name,
                $manifest->version,
                $manifest->core ? 'core' : 'third-party',
                count($manifest->capabilities),
                count($manifest->dependencies),
                ($order[$identifier] ?? 0) + 1,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a[6] <=> $b[6]);
        $this->table(['Identifier', 'Name', 'Version', 'Trust', 'Capabilities', 'Dependencies', 'Boot'], $rows);

        return self::SUCCESS;
    }
}
