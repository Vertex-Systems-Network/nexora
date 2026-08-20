<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\FreshInstallDependencyTrust;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Installation\SourceActivationIdentity;
use App\Nexora\Installation\SourceActivationHandshake;
use App\Nexora\Installation\SystemRequirementChecker;
use Illuminate\Console\Command;

final class InstallerDoctorCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    protected $signature = 'nexora:install:doctor {--json : Emit machine-readable JSON}';

    protected $description = 'Inspect Nexora installation readiness without changing the database.';

    public function handle(
        SystemRequirementChecker $requirements,
        InstallationState $state,
        FreshInstallDependencyTrust $dependencyTrust,
        SourceActivationIdentity $sourceActivation,
        SourceActivationHandshake $sourceHandshake,
    ): int {
        $source = $sourceActivation->inspect();
        $sourceHandshakeState = $sourceHandshake->inspect($source);
        $server = $requirements->check();
        $lock = $state->inspect();
        $installed = $state->isInstalled();
        $dependencies = $installed
            ? null
            : $dependencyTrust->inspect();

        $lockReady = ! $installed || ($lock['valid'] ?? false) === true;
        $ready = ($source['status'] ?? 'fail') === 'pass'
            && (bool) $server['ready']
            && $lockReady
            && ($dependencies === null || ($dependencies['status'] ?? 'fail') === 'pass');

        $payload = [
            'installed' => $installed,
            'source_activation' => $source,
            'source_activation_handshake' => $sourceHandshakeState,
            'installation_lock' => [
                'status' => $lock['status'] ?? 'unknown',
                'valid' => (bool) ($lock['valid'] ?? false),
                'sealed' => (bool) ($lock['sealed'] ?? false),
                'schema' => $lock['schema'] ?? null,
                'errors' => $lock['errors'] ?? [],
            ],
            ...$server,
            'ready' => $ready,
            'server' => $server,
            'fresh_install_dependency_trust' => $dependencies,
        ];

        if ($this->option('json')) {
            $this->line(json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));

            return $ready ? self::SUCCESS : self::FAILURE;
        }

        $this->info($installed
            ? 'Nexora installation lock is present.'
            : 'Nexora installation is pending.');

        $this->line(sprintf(
            '[%s] Source activation — %s · %s · %s',
            ($source['status'] ?? 'fail') === 'pass' ? 'PASS' : 'BLOCK',
            (string) ($source['platform_version'] ?? 'unknown'),
            (string) ($source['running_protocol'] ?? 'unknown'),
            (string) ($source['running_generation'] ?? 'unknown'),
        ));
        foreach ((array) ($source['errors'] ?? []) as $error) {
            $this->line('  - '.$error);
        }

        $this->line(sprintf(
            '[%s] Web activation handshake — disk %d/%d · runtime %d/%d · %s',
            ($sourceHandshakeState['status'] ?? 'pending') === 'pass' ? 'PASS' : 'INFO',
            (int) ($source['critical_source_files_matched'] ?? 0),
            (int) ($source['critical_source_files'] ?? 0),
            (int) ($source['runtime_classes_matched'] ?? 0),
            (int) ($source['runtime_classes_total'] ?? 0),
            (string) ($sourceHandshakeState['status'] ?? 'pending'),
        ));

        if ($installed) {
            $this->line(sprintf(
                '[%s] Installation lock — %s',
                ($lock['valid'] ?? false) === true ? 'PASS' : 'BLOCK',
                (string) ($lock['status'] ?? 'unknown'),
            ));
            foreach ((array) ($lock['errors'] ?? []) as $error) {
                $this->line('  - '.$error);
            }
        }

        foreach ((array) $server['checks'] as $check) {
            $this->line(sprintf(
                '[%s] %s — %s',
                $check['ok'] ? 'PASS' : 'BLOCK',
                $check['label'],
                $check['detail'],
            ));
        }

        if ($dependencies !== null) {
            $this->line(sprintf(
                '[%s] Dependency trust — %s',
                ($dependencies['status'] ?? 'fail') === 'pass' ? 'PASS' : 'BLOCK',
                (string) ($dependencies['trust_mode'] ?? 'unknown'),
            ));

            foreach ((array) ($dependencies['errors'] ?? []) as $error) {
                $this->line('  - '.$error);
            }

            if (($dependencies['review_required'] ?? false) === true
                && ($dependencies['status'] ?? 'fail') === 'pass') {
                $this->warn(
                    'Fresh installation can continue with bootstrap-verified dependency identity, '
                    .'but reviewed-lock attestation is still required before N1.0 certification / HA release closure.',
                );
            }
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
