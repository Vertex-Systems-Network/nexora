<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use ReflectionClass;
use RuntimeException;

final class SourceActivationIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function __construct(private readonly SourceSetIntegrity $sourceSet)
    {
    }

    /** @return array<string,mixed> */
    public function inspect(): array
    {
        $reflection = new ReflectionClass(Installer::class);
        $loadedPath = $reflection->getFileName();
        $expectedPath = realpath(base_path('app/Nexora/Installation/Installer.php')) ?: null;
        $loadedRealPath = is_string($loadedPath) ? (realpath($loadedPath) ?: $loadedPath) : null;
        $loadedHash = is_string($loadedRealPath) && is_file($loadedRealPath)
            ? hash_file('sha256', $loadedRealPath)
            : null;

        $expectedProtocol = (string) config('installer.source.expected_protocol', 'v5.9');
        $expectedGeneration = (string) config('installer.source.expected_generation', 'n1-v5.29');
        $expectedInstallerHash = strtolower(trim((string) config('installer.source.installer_sha256', '')));
        $runningProtocol = Installer::PROTOCOL;
        $runningGeneration = Installer::SOURCE_GENERATION;
        $errors = [];

        if ($runningProtocol !== $expectedProtocol) {
            $errors[] = "Running installer protocol [{$runningProtocol}] does not match expected [{$expectedProtocol}].";
        }

        if ($runningGeneration !== $expectedGeneration) {
            $errors[] = "Running source generation [{$runningGeneration}] does not match expected [{$expectedGeneration}].";
        }

        if ($expectedPath === null || $loadedRealPath === null || $expectedPath !== $loadedRealPath) {
            $errors[] = sprintf(
                'Loaded Installer.php path [%s] does not match project source path [%s].',
                $loadedRealPath ?? 'unknown',
                $expectedPath ?? 'missing',
            );
        }

        if ($expectedInstallerHash === '' || preg_match('/^[a-f0-9]{64}$/', $expectedInstallerHash) !== 1) {
            $errors[] = 'Expected Installer.php SHA-256 is not configured correctly.';
        } elseif (! is_string($loadedHash) || ! hash_equals($expectedInstallerHash, strtolower($loadedHash))) {
            $errors[] = sprintf(
                'Loaded Installer.php SHA-256 [%s] does not match package SHA-256 [%s].',
                is_string($loadedHash) ? $loadedHash : 'unavailable',
                $expectedInstallerHash,
            );
        }

        $sourceSet = $this->sourceSet->inspect();
        if (($sourceSet['status'] ?? 'fail') !== 'pass') {
            foreach ((array) ($sourceSet['errors'] ?? []) as $error) {
                $errors[] = 'Critical source set: '.$error;
            }
        }

        $runtimeClasses = $this->runtimeClassState($expectedGeneration);
        if (($runtimeClasses['status'] ?? 'fail') !== 'pass') {
            foreach ((array) ($runtimeClasses['errors'] ?? []) as $error) {
                $errors[] = 'Runtime class set: '.$error;
            }
        }

        $opcacheEnabled = filter_var((string) ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);
        $validateTimestamps = filter_var((string) ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOLEAN);
        $revalidateFrequency = (int) ini_get('opcache.revalidate_freq');
        $sourceSetFingerprint = $sourceSet['source_set_fingerprint'] ?? null;

        $materials = [
            'platform_version' => (string) config('nexora.version', 'unknown'),
            'expected_protocol' => $expectedProtocol,
            'running_protocol' => $runningProtocol,
            'expected_generation' => $expectedGeneration,
            'running_generation' => $runningGeneration,
            'installer_sha256' => $loadedHash,
            'source_set_fingerprint' => $sourceSetFingerprint,
            'runtime_class_fingerprint' => $runtimeClasses['fingerprint'] ?? null,
            'php_sapi' => PHP_SAPI,
        ];

        return [
            'status' => $errors === [] ? 'pass' : 'fail',
            'current' => $errors === [],
            'platform_version' => $materials['platform_version'],
            'expected_protocol' => $expectedProtocol,
            'running_protocol' => $runningProtocol,
            'expected_generation' => $expectedGeneration,
            'running_generation' => $runningGeneration,
            'installer_path' => $loadedRealPath,
            'expected_installer_path' => $expectedPath,
            'installer_sha256' => $loadedHash,
            'expected_installer_sha256' => $expectedInstallerHash,
            'source_set_status' => $sourceSet['status'] ?? 'fail',
            'source_set_fingerprint' => $sourceSetFingerprint,
            'critical_source_files' => (int) ($sourceSet['file_count'] ?? 0),
            'critical_source_files_matched' => (int) ($sourceSet['matched_files'] ?? 0),
            'critical_source_manifest_sha256' => $sourceSet['manifest_sha256'] ?? null,
            'critical_source_manifest_expected_sha256' => $sourceSet['expected_manifest_sha256'] ?? null,
            'critical_source_file_results' => $sourceSet['files'] ?? [],
            'runtime_class_status' => $runtimeClasses['status'] ?? 'fail',
            'runtime_class_fingerprint' => $runtimeClasses['fingerprint'] ?? null,
            'runtime_classes_total' => (int) ($runtimeClasses['total'] ?? 0),
            'runtime_classes_matched' => (int) ($runtimeClasses['matched'] ?? 0),
            'runtime_class_results' => $runtimeClasses['classes'] ?? [],
            'php_sapi' => PHP_SAPI,
            'opcache_enabled' => $opcacheEnabled,
            'opcache_validate_timestamps' => $validateTimestamps,
            'opcache_revalidate_freq' => $revalidateFrequency,
            'errors' => array_values(array_unique($errors)),
            'fingerprint' => $this->fingerprint($materials),
        ];
    }

    /** @return array<string,mixed> */
    public function assertCurrent(): array
    {
        $state = $this->inspect();
        if (($state['status'] ?? 'fail') === 'pass') {
            return $state;
        }

        throw new RuntimeException(
            'Nexora source activation mismatch. The PHP process is not executing the exact critical installer source set from this package. '
            .implode(' ', (array) ($state['errors'] ?? []))
            .' Run `php artisan nexora:source:activate --assert-current`, reload/restart Laragon web/PHP, '
            .'then verify `/install/source-status` before retrying installation.',
        );
    }

    /** @return array<string,mixed> */
    private function runtimeClassState(string $expectedGeneration): array
    {
        $classes = [
            \App\Nexora\Installation\Installer::class,
            \App\Http\Controllers\Install\InstallerController::class,
            \App\Nexora\Foundation\Runtime\FreshInstallDependencyTrust::class,
            \App\Nexora\Foundation\Runtime\ReviewedDependencyState::class,
            \App\Nexora\Installation\InstallationState::class,
            \App\Nexora\Installation\SourceActivationIdentity::class,
            \App\Nexora\Installation\SourceSetIntegrity::class,
            \App\Nexora\Installation\SourceActivationHandshake::class,
            \App\Console\Commands\Nexora\SourceActivateCommand::class,
            \App\Console\Commands\Nexora\SourceStatusCommand::class,
            \App\Console\Commands\Nexora\InstallerDoctorCommand::class,
            \App\Providers\NexoraServiceProvider::class,
            \App\Nexora\Installation\InstallationRunControl::class,
            \App\Nexora\Installation\InstallationResumeIdentity::class,
            \App\Nexora\Installation\DatabaseProvisioner::class,
            \App\Nexora\Installation\EnvironmentWriter::class,
            \App\Nexora\Installation\DatabaseBackupManager::class,
            \App\Nexora\Installation\SystemRequirementChecker::class,
            \App\Nexora\Installation\Database\DatabaseDriverRegistry::class,
            \App\Nexora\Security\Password\PasswordStrengthEvaluator::class,
            \App\Nexora\Cloud\Services\RuntimeHostClockIdentity::class,
            \App\Nexora\Foundation\Runtime\RuntimeWritableTempDirectory::class,
            \App\Console\Commands\Nexora\RuntimeHostStatusCommand::class,
            \App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity::class,
            \App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity::class,
            \App\Nexora\Cloud\Services\RuntimeProcessPlane::class,
            \App\Nexora\Cloud\Services\RuntimeActivationIdentity::class,
            \App\Nexora\Installation\RuntimeInstallationReadiness::class,
            \App\Console\Commands\Nexora\RuntimeInstallReadinessCommand::class,
            \App\Nexora\Cloud\Services\RuntimeDeploymentIdentity::class,
            \App\Nexora\Cloud\Services\RuntimeVersionGuard::class,
            \App\Nexora\Installation\RuntimePostInstallHandoff::class,
            \App\Console\Commands\Nexora\RuntimePostInstallStatusCommand::class,
            \App\Console\Commands\Nexora\RuntimePostInstallReconcileCommand::class,
        ];
        $errors = [];
        $results = [];
        $materials = [];
        $matched = 0;

        foreach ($classes as $class) {
            try {
                $reflection = new ReflectionClass($class);
                $generation = $reflection->hasConstant('RUNTIME_SOURCE_GENERATION')
                    ? (string) $reflection->getConstant('RUNTIME_SOURCE_GENERATION')
                    : null;
            } catch (\Throwable $exception) {
                $generation = null;
                $errors[] = "Unable to load critical runtime class [{$class}]: {$exception->getMessage()}";
            }

            $ok = $generation === $expectedGeneration;
            if ($ok) {
                $matched++;
            } else {
                $errors[] = sprintf(
                    'Loaded class generation mismatch [%s: %s != %s].',
                    $class,
                    $generation ?? 'missing',
                    $expectedGeneration,
                );
            }

            $results[$class] = [
                'ok' => $ok,
                'generation' => $generation,
                'expected_generation' => $expectedGeneration,
            ];
            $materials[$class] = $generation;
        }

        ksort($materials, SORT_STRING);

        return [
            'status' => $errors === [] ? 'pass' : 'fail',
            'total' => count($classes),
            'matched' => $matched,
            'classes' => $results,
            'errors' => array_values(array_unique($errors)),
            'fingerprint' => hash('sha256', json_encode(
                $materials,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            )),
        ];
    }

    /** @param array<string,mixed> $materials */
    private function fingerprint(array $materials): string
    {
        ksort($materials, SORT_STRING);

        return hash('sha256', json_encode(
            $materials,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
