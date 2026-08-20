<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class UpgradeLineageExportCommand extends Command
{
    protected $signature = 'nexora:upgrade:lineage {--output= : Optional JSON output path}';

    protected $description = 'Export non-secret installed release, dependency, and upgrade lineage for operational audit.';

    public function handle(InstallationState $installation): int
    {
        $metadata = $installation->metadata();
        if (! is_array($metadata)) {
            $this->error('Nexora installation metadata is unavailable.');
            return self::FAILURE;
        }

        $lineage = [
            'schema' => 1,
            'generated_at' => now()->toIso8601String(),
        ];

        foreach ($this->lineageKeys() as $key) {
            $lineage[$key] = $metadata[$key] ?? null;
        }

        $json = json_encode(
            $lineage,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
        $output = trim((string) $this->option('output'));

        if ($output === '') {
            $this->line($json);
            return self::SUCCESS;
        }

        return $this->writeOutput($output, $json);
    }

    /** @return list<string> */
    private function lineageKeys(): array
    {
        return [
            'installation_id', 'installed_at', 'previous_version', 'version',
            'last_upgrade_id', 'upgraded_at', 'previous_release_seal_sha256',
            'release_seal_sha256', 'release_signer_key_id',
            'release_signer_public_key_sha256', 'update_trust_anchor_sha256',
            'release_admitted_at', 'previous_deployment_generation',
            'deployment_generation', 'release_source_tree_sha256',
            'frontend_manifest_sha256', 'cache_namespace', 'session_schema',
            'composer_lock_sha256', 'package_lock_sha256',
            'runtime_dependency_fingerprint', 'laravel_framework_version',
            'dependency_reconciled_at', 'dependency_reconciled_by',
            'activation_epoch', 'runtime_activation_fingerprint',
            'runtime_activation_cache_sha256', 'runtime_activated_at',
            'last_upgrade_runtime_activation_fingerprint',
            'last_upgrade_backup_sha256', 'last_upgrade_restore_readiness_sha256',
            'last_upgrade_database_fingerprint_sha256', 'last_upgrade_pre_health_sha256',
            'last_upgrade_post_health_sha256', 'last_upgrade_compatibility_assessment_sha256',
            'last_upgrade_migration_ledger_before_sha256',
            'last_upgrade_migration_ledger_after_sha256',
            'last_upgrade_migration_convergence_sha256',
            'last_upgrade_cluster_preflight_sha256', 'runtime_engine_fingerprint',
            'php_version', 'extension_profile_sha256', 'pdo_drivers_sha256',
            'last_upgrade_runtime_engine_fingerprint', 'database_data_plane_fingerprint',
            'database_schema_fingerprint', 'database_server_version',
            'database_session_profile_sha256', 'last_upgrade_database_schema_before_sha256',
            'last_upgrade_database_schema_after_sha256', 'runtime_storage_fingerprint',
            'object_storage_disk', 'media_storage_disk', 'backup_storage_disk',
            'storage_deep_probe_sha256', 'last_upgrade_storage_fingerprint',
            'runtime_service_fingerprint', 'service_deep_probe_sha256',
            'cache_service_store', 'queue_service_connection', 'mail_service_default',
            'last_upgrade_service_fingerprint', 'runtime_resource_fingerprint',
            'resource_deep_probe_sha256', 'last_upgrade_resource_fingerprint',
            'runtime_policy_fingerprint', 'runtime_policy_deep_sha256',
            'last_upgrade_policy_fingerprint', 'runtime_process_fingerprint',
            'last_upgrade_process_fingerprint',
        ];
    }

    private function writeOutput(string $output, string $json): int
    {
        $absolute = str_starts_with($output, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $output) === 1;
        $path = $absolute ? $output : base_path($output);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error('Unable to create lineage output directory.');
            return self::FAILURE;
        }
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            $this->error('Unable to write lineage export.');
            return self::FAILURE;
        }

        $this->info('Lineage export written: '.$path);
        return self::SUCCESS;
    }
}
