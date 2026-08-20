<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimeHostStatusCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    protected $signature = 'nexora:runtime:host-status
        {--deep : Run strict bounded clock/filesystem/entropy probes}
        {--installation : Evaluate the installer-safe host profile}
        {--assert-installed : Compare host fingerprint with installation lineage}';

    protected $description = 'Inspect Nexora host/platform/clock compatibility without exposing secrets.';

    public function handle(RuntimeHostClockIdentity $host, InstallationState $installation): int
    {
        $state = $this->option('installation')
            ? $host->installationAttestation()
            : $host->current((bool) $this->option('deep'));

        $installed = $installation->metadata();
        $expected = is_array($installed)
            ? strtolower(trim((string) ($installed['runtime_host_fingerprint'] ?? '')))
            : '';
        $match = $expected === '' || hash_equals($expected, (string) $state['fingerprint']);

        $state['installed_runtime_host_fingerprint'] = $expected !== '' ? $expected : null;
        $state['installed_match'] = $match;

        if ($this->option('assert-installed') && $expected === '') {
            $state['status'] = 'fail';
            $state['error'] = 'installed runtime host fingerprint is missing';
        } elseif ($this->option('assert-installed') && ! $match) {
            $state['status'] = 'fail';
            $state['error'] = 'current host fingerprint does not match installed lineage';
        }

        $this->line(json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        return ($state['status'] ?? 'fail') === 'pass'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
