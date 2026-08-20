<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\RuntimeInstallationReadiness;
use Illuminate\Console\Command;

final class RuntimeInstallReadinessCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    protected $signature = 'nexora:runtime:install-readiness
        {--json : Emit the complete machine-readable readiness report}
        {--assert-ready : Exit non-zero unless every installer-safe component is PASS}';

    protected $description = 'Inspect all installer-safe runtime attestations before database mutation.';

    public function handle(RuntimeInstallationReadiness $readiness): int
    {
        $state = $readiness->inspect();

        if ((bool) $this->option('json')) {
            $this->line(json_encode(
                $state,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));
        } else {
            $this->line(sprintf(
                'Nexora %s · %s · %s',
                (string) ($state['platform_version'] ?? 'unknown'),
                (string) ($state['installer_protocol'] ?? 'unknown'),
                (string) ($state['source_generation'] ?? 'unknown'),
            ));
            $this->line(sprintf(
                'Installation readiness: %s · %d/%d components',
                strtoupper((string) ($state['status'] ?? 'fail')),
                (int) ($state['components_passed'] ?? 0),
                (int) ($state['components_total'] ?? 0),
            ));

            foreach ((array) ($state['components'] ?? []) as $name => $component) {
                $this->line(sprintf(
                    '[%s] %s',
                    strtoupper((string) ($component['status'] ?? 'fail')),
                    str_replace('_', ' ', (string) $name),
                ));
            }

            foreach ((array) ($state['blocking_reasons'] ?? []) as $reason) {
                $this->error((string) $reason);
            }
            foreach ((array) ($state['warnings'] ?? []) as $warning) {
                $this->warn((string) $warning);
            }
        }

        if ((bool) $this->option('assert-ready')) {
            return ($state['status'] ?? 'fail') === 'pass'
                ? self::SUCCESS
                : self::FAILURE;
        }

        return ($state['status'] ?? 'fail') === 'pass'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
