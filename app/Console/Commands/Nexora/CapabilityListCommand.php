<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use Illuminate\Console\Command;

final class CapabilityListCommand extends Command
{
    protected $signature = 'nexora:capability:list {--risk= : Filter by risk level}';
    protected $description = 'List capabilities known to the Nexora runtime.';

    public function handle(CapabilityRegistryContract $capabilities): int
    {
        $risk = $this->option('risk');
        $rows = [];
        foreach ($capabilities->all() as $capability) {
            if (is_string($risk) && $risk !== '' && $capability->risk->value !== $risk) {
                continue;
            }
            $rows[] = [$capability->slug, $capability->name, $capability->group, $capability->risk->value];
        }

        $this->table(['Capability', 'Name', 'Group', 'Risk'], $rows);

        return self::SUCCESS;
    }
}
