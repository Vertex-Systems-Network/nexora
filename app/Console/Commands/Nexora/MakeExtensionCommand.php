<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Forge\Services\ForgeExtensionScaffolder;
use Illuminate\Console\Command;
use Throwable;

final class MakeExtensionCommand extends Command
{
    protected $signature = 'nexora:make:extension
        {identifier : Namespaced package identifier such as vendor.extension}
        {--name= : Human-readable package name}
        {--type=extension : extension, app, integration or studio-pack}
        {--dry-run : Preview the deterministic scaffold without writing files}
        {--force : Refresh generated files only when the destination is owned by the same Forge scaffold}';

    protected $description = 'Create or preview a safe Forge-compatible Nexora extension source scaffold.';

    public function handle(ForgeExtensionScaffolder $forge): int
    {
        try {
            $identifier = (string) $this->argument('identifier');
            $name = $this->option('name');
            $type = (string) $this->option('type');
            $plan = $forge->plan($identifier, is_string($name) ? $name : null, $type);

            if ((bool) $this->option('dry-run')) {
                $this->info('Forge dry run — no filesystem changes were made.');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Identifier', $plan['identifier']],
                        ['Name', $plan['name']],
                        ['Type', $plan['type']],
                        ['Destination', $plan['target']],
                        ['Exists', $plan['exists'] ? 'yes' : 'no'],
                        ['Forge owned', $plan['forge_owned'] ? 'yes' : 'no'],
                    ],
                );
                $this->line('Generated files:');
                foreach ($plan['files'] as $file) {
                    $this->line(' - '.$file);
                }
                $this->newLine();
                $this->line('Trust boundary: Forge only generates source. Installation still requires package review and Sentinel ALLOW.');

                return self::SUCCESS;
            }

            $result = $forge->create(
                $identifier,
                is_string($name) ? $name : null,
                $type,
                (bool) $this->option('force'),
            );

            $this->info(($result['refreshed'] ? 'Refreshed' : 'Created').' Forge extension scaffold: '.$result['target']);
            $this->line('Next: add only requested capabilities, package/sign outside runtime storage, then upload through Sentinel.');
            $this->line('Forge never installs, enables, grants trust or bypasses Sentinel.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
