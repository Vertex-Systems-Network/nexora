<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use Illuminate\Console\Command;

final class RuntimeCompatibilityStatusCommand extends Command
{
    protected $signature = 'nexora:runtime:compatibility-status
        {--deep : Include deployment drift and reviewed dependency diagnostics}
        {--json : Emit JSON only}';

    protected $description = 'Explain exactly why the current Nexora runtime is compatible or quarantined.';

    public function handle(
        RuntimeVersionGuard $guard,
        RuntimeDeploymentIdentity $deployment,
        FrameworkCompatibility $framework,
        ReviewedDependencyState $dependencies,
    ): int {
        $assessment = $guard->assess();
        $payload = [
            'status' => $assessment['compatible'] ? 'pass' : 'fail',
            'mismatches' => $assessment['mismatches'] ?? [],
            'runtime' => $assessment,
            'framework' => $framework->status(),
        ];

        if ($this->option('deep')) {
            $payload['deployment_drift'] = $deployment->installedDriftAssessment();
            $payload['reviewed_dependencies'] = $dependencies->inspect();
        }

        $payload['next_action'] = $this->nextAction($payload);

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if (! $this->option('json')) {
            $this->line('Nexora runtime compatibility: '.strtoupper((string) $payload['status']));
        }

        $this->line($json);

        return $assessment['compatible'] ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $payload */
    private function nextAction(array $payload): string
    {
        $framework = (array) ($payload['framework'] ?? []);
        if (($framework['status'] ?? 'fail') !== 'pass') {
            return 'Install a Laravel 13.x version inside the certified range, then rerun diagnostics.';
        }

        $drift = (array) ($payload['deployment_drift'] ?? []);
        $reviewed = (array) ($payload['reviewed_dependencies'] ?? []);

        if (($drift['dependency_only'] ?? false) === true) {
            if (($reviewed['status'] ?? 'fail') !== 'pass') {
                return 'Refresh/review dependency locks, then rerun dependency status.';
            }

            $runningLaravel = ltrim((string) ($framework['installed_version'] ?? ''), 'v');
            $lockedLaravel = ltrim((string) ($reviewed['laravel_framework_locked_version'] ?? ''), 'v');
            if ($runningLaravel === ''
                || $lockedLaravel === ''
                || ! version_compare($runningLaravel, $lockedLaravel, '==')) {
                return 'Install the reviewed Composer lock first, then rerun compatibility status before reconciliation.';
            }

            return 'Enter maintenance mode and run nexora:runtime:dependency-reconcile with a real operator identity.';
        }

        if (($payload['status'] ?? 'fail') === 'pass') {
            return 'No runtime compatibility action is required.';
        }

        return 'The mismatch is broader than a dependency refresh. Use the normal Nexora upgrade/recovery workflow.';
    }
}
