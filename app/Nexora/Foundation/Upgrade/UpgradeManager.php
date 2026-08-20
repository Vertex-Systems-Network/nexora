<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Installation\InstallationState;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeKeyRotationService;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

final readonly class UpgradeManager
{
    public function __construct(
        private UpgradeCompatibilityService $compatibility,
        private UpgradeBackupVerifier $backups,
        private UpgradePlanStore $plans,
        private InstallationState $installation,
        private TrustedUpdateAdmission $trustedUpdate,
        private UpgradeTransactionJournal $journal,
        private UpgradeMaintenanceLease $maintenanceLease,
        private UpgradePostHealthCheck $health,
        private UpgradeClusterCoordinator $cluster,
        private UpgradeMigrationLedger $migrationLedger,
        private UpgradeMigrationSafety $migrationSafety,
        private RuntimeDeploymentIdentity $deployment,
        private RuntimeActivationIdentity $activation,
        private RuntimeEnvironmentIdentity $runtimeEnvironment,
        private RuntimeKeyRotationService $keyRotation,
        private RuntimeEngineIdentity $runtimeEngine,
        private DatabaseDataPlaneIdentity $databaseDataPlane,
        private RuntimeStorageDataPlaneIdentity $storageDataPlane,
        private RuntimeServiceDataPlaneIdentity $serviceDataPlane,
        private RuntimeHostClockIdentity $hostClock,
        private RuntimeResourceEnvelopeIdentity $resources,
        private RuntimePolicyPlaneIdentity $policyPlane,
        private RuntimeProcessPlane $processPlane,
        private FrameworkCompatibility $frameworkCompatibility,
        private ReviewedDependencyState $reviewedDependencies,
    ) {}

    /** @return array<string,mixed> */
    public function plan(?string $backupId = null, ?string $externalBackupEvidence = null): array
    {
        $assessment = $this->compatibility->assess();
        $trustedUpdate = $this->trustedUpdate->verify();
        $sourceVersion = (string) ($assessment['source_version'] ?? '');
        $targetVersion = (string) ($assessment['target_version'] ?? config('nexora.version'));

        $backup = $this->backups->verify(
            $backupId,
            $externalBackupEvidence,
            $sourceVersion,
        );
        $migrationLedger = $this->migrationLedger->snapshot();
        $migrationSafety = $this->migrationSafety->assess(
            (array) ($assessment['pending_migrations'] ?? []),
        );
        $cluster = $this->cluster->assess($sourceVersion, $targetVersion);

        $deployment = $this->deployment->current();
        $runtimeEnvironment = $this->runtimeEnvironment->current();
        $runtimeActivation = $this->activation->current();
        $runtimeEngine = $this->runtimeEngine->current();
        $databaseDataPlane = $this->databaseDataPlane->current(true);
        $storageDataPlane = $this->storageDataPlane->current(true);
        $serviceDataPlane = $this->serviceDataPlane->current(true);
        $hostClock = $this->hostClock->current(true);
        $resourceEnvelope = $this->resources->current(true);
        $policyPlane = $this->policyPlane->current(true);
        $processPlane = $this->processPlane->policy();
        $framework = $this->frameworkCompatibility->status();
        $dependencies = $this->reviewedDependencies->inspect();

        $errors = (array) ($assessment['errors'] ?? []);
        $this->appendPlanSafetyErrors(
            $errors,
            $trustedUpdate,
            $backup,
            $migrationLedger,
            $migrationSafety,
            $cluster,
            $databaseDataPlane,
            $storageDataPlane,
            $serviceDataPlane,
            $hostClock,
            $resourceEnvelope,
            $policyPlane,
            $processPlane,
            $framework,
            $dependencies,
        );

        $databaseSessionHash = hash(
            'sha256',
            json_encode(
                $databaseDataPlane['session_profile'] ?? [],
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
        );

        $createdAt = $this->hostClock->databaseNow();
        $plan = [
            'upgrade_id' => (string) Str::uuid(),
            'status' => $errors === [] ? 'ready' : 'blocked',
            'source_version' => $assessment['source_version'],
            'target_version' => $assessment['target_version'],
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $createdAt
                ->copy()
                ->addMinutes((int) config('nexora-upgrade.plan_ttl_minutes', 120))
                ->toIso8601String(),
            'backup' => $backup,
            'assessment' => $assessment,
            'compatibility_assessment_sha256' => $assessment['assessment_sha256'] ?? null,
            'migration_ledger_before' => $migrationLedger,
            'migration_safety' => $migrationSafety,
            'cluster_preflight' => $cluster,
            'target_deployment' => $deployment,
            'target_runtime_environment' => $runtimeEnvironment,
            'source_runtime_activation' => $runtimeActivation,
            'target_runtime_engine' => $runtimeEngine,
            'source_database_data_plane' => [
                'fingerprint' => $databaseDataPlane['fingerprint'] ?? null,
                'schema_fingerprint' => $databaseDataPlane['schema_fingerprint'] ?? null,
                'normalized_server_version' => $databaseDataPlane['normalized_server_version'] ?? null,
                'database_name_sha256' => $databaseDataPlane['database_name_sha256'] ?? null,
                'session_profile_sha256' => $databaseSessionHash,
            ],
            'source_storage_data_plane' => [
                'fingerprint' => $storageDataPlane['fingerprint'] ?? null,
                'object_profile_sha256' => $storageDataPlane['roles']['object']['profile_sha256'] ?? null,
                'media_profile_sha256' => $storageDataPlane['roles']['media']['profile_sha256'] ?? null,
                'backup_profile_sha256' => $storageDataPlane['roles']['backup']['profile_sha256'] ?? null,
                'deep_sha256' => $storageDataPlane['deep']['deep_sha256'] ?? null,
            ],
            'source_service_data_plane' => [
                'fingerprint' => $serviceDataPlane['fingerprint'] ?? null,
                'deep_sha256' => $serviceDataPlane['deep']['deep_sha256'] ?? null,
                'cache_store' => $serviceDataPlane['materials']['cache']['store'] ?? null,
                'queue_connection' => $serviceDataPlane['materials']['queue']['connection'] ?? null,
                'mail_default' => $serviceDataPlane['materials']['mail']['default'] ?? null,
            ],
            'source_host_clock' => [
                'fingerprint' => $hostClock['fingerprint'] ?? null,
                'deep_sha256' => $hostClock['deep']['deep_sha256'] ?? null,
                'clock_skew_ms' => $hostClock['deep']['details']['clock']['skew_ms'] ?? null,
                'os_family' => $hostClock['materials']['os_family'] ?? null,
                'machine_arch' => $hostClock['materials']['machine_arch'] ?? null,
            ],
            'source_resource_envelope' => [
                'fingerprint' => $resourceEnvelope['fingerprint'] ?? null,
                'deep_sha256' => $resourceEnvelope['deep']['deep_sha256'] ?? null,
                'memory_headroom_bytes' => $resourceEnvelope['deep']['details']['memory']['headroom_bytes'] ?? null,
                'temp_free_bytes' => $resourceEnvelope['deep']['details']['temp']['free_bytes'] ?? null,
                'storage_free_bytes' => $resourceEnvelope['deep']['details']['storage']['free_bytes'] ?? null,
                'transfer_free_bytes' => $resourceEnvelope['deep']['details']['transfer']['free_bytes'] ?? null,
            ],
            'source_policy_plane' => [
                'fingerprint' => $policyPlane['fingerprint'] ?? null,
                'deep_sha256' => $policyPlane['deep']['deep_sha256'] ?? null,
            ],
            'source_process_plane' => [
                'fingerprint' => $processPlane['fingerprint'] ?? null,
                'lease_seconds' => $processPlane['materials']['lease_seconds'] ?? null,
                'minimum_web_nodes' => $processPlane['materials']['minimum_web_nodes'] ?? null,
                'minimum_queue_nodes' => $processPlane['materials']['minimum_queue_nodes'] ?? null,
                'minimum_scheduler_nodes' => $processPlane['materials']['minimum_scheduler_nodes'] ?? null,
            ],
            'source_dependency_state' => [
                'fingerprint' => $dependencies['fingerprint'] ?? null,
                'laravel_framework_version' => $framework['installed_version'] ?? null,
                'laravel_framework_locked_version' => $dependencies['laravel_framework_locked_version'] ?? null,
                'composer_lock_sha256' => $dependencies['hashes']['composer_lock_sha256'] ?? null,
                'package_lock_sha256' => $dependencies['hashes']['package_lock_sha256'] ?? null,
            ],
            'trusted_update' => $trustedUpdate,
            'errors' => $errors,
            'operator_sequence' => [
                'Admit the externally trusted signed release and stage it into an empty directory before replacing source files.',
                'Verify the source-version backup plus guarded restore-readiness plan and confirm Nexora owns the maintenance transition.',
                'For multi-node targets, verify shared storage and service convergence, drain peer traffic, ' .
                'wait for zero in-flight activity, empty the queue backlog, and ensure scheduler leadership ' .
                'is local or expired before migrations.',
                'Run nexora:upgrade:apply only after reviewing extension/theme compatibility, migration-ledger state, and reviewed dependency locks.',
                'If apply fails after migrations begin, keep maintenance mode enabled and restore the verified backup instead of attempting blind down-migrations.',
            ],
        ];

        return $this->plans->write($plan);
    }


    /** @return array<string,mixed> */
    public function apply(): array
    {
        $plan = $this->plans->read();
        if (! is_array($plan)) {
            throw new \RuntimeException(
                'No active Nexora upgrade plan exists. Run nexora:upgrade:plan first.',
            );
        }
        if (($plan['status'] ?? null) !== 'ready') {
            throw new \RuntimeException(
                'The active Nexora upgrade plan is blocked. Resolve preflight errors and create a new plan.',
            );
        }

        $expiresAt = strtotime((string) ($plan['expires_at'] ?? ''));
        if ($expiresAt === false || $expiresAt < $this->hostClock->databaseNow()->getTimestamp()) {
            throw new \RuntimeException(
                'The active Nexora upgrade plan expired. Run preflight again.',
            );
        }
        if (($plan['target_version'] ?? null) !== (string) config('nexora.version')) {
            throw new \RuntimeException(
                'Upgrade plan target version does not match the currently deployed source tree.',
            );
        }

        $deployment = $this->deployment->current();
        $runtimeEnvironment = $this->runtimeEnvironment->current();
        $runtimeActivation = $this->activation->current();
        $runtimeEngine = $this->runtimeEngine->current();
        $databaseDataPlane = $this->databaseDataPlane->current(true);
        $storageDataPlane = $this->storageDataPlane->current(true);
        $serviceDataPlane = $this->serviceDataPlane->current(true);
        $hostClock = $this->hostClock->current(true);
        $resourceEnvelope = $this->resources->current(true);
        $policyPlane = $this->policyPlane->current(true);
        $processPlane = $this->processPlane->policy();
        $framework = $this->frameworkCompatibility->assertCompatible();
        $dependencies = $this->reviewedDependencies->inspect();

        $this->assertPlanRuntimeIdentity(
            $plan,
            $deployment,
            $runtimeEnvironment,
            $runtimeActivation,
            $runtimeEngine,
            $databaseDataPlane,
            $storageDataPlane,
            $serviceDataPlane,
            $hostClock,
            $resourceEnvelope,
            $policyPlane,
            $processPlane,
            $framework,
            $dependencies,
        );

        if ($this->keyRotation->read() !== null) {
            throw new \RuntimeException(
                'An APP_KEY rotation receipt is active; code/schema upgrade apply is blocked until key rotation is committed or aborted.',
            );
        }
        if ((bool) config('nexora-upgrade.require_restore_readiness', true)
            && ! ($plan['backup']['restore_ready'] ?? false)) {
            throw new \RuntimeException(
                'Upgrade plan no longer contains a verified restore-readiness proof. Create a new plan.',
            );
        }

        $assessment = $this->compatibility->assess();
        $trustedUpdate = $this->trustedUpdate->verify();
        if (! $trustedUpdate['ok']) {
            throw new \RuntimeException(
                'Trusted update admission changed since planning: '
                .implode('; ', $trustedUpdate['errors']),
            );
        }
        if (($plan['trusted_update']['receipt_sha256'] ?? null)
            !== ($trustedUpdate['receipt_sha256'] ?? null)) {
            throw new \RuntimeException(
                'Trusted update admission receipt changed after the upgrade plan was sealed; create a new plan.',
            );
        }
        if (($assessment['status'] ?? null) !== 'pass') {
            throw new \RuntimeException(
                'Upgrade compatibility changed since planning; create a new plan.',
            );
        }
        if (($plan['compatibility_assessment_sha256'] ?? null)
            !== ($assessment['assessment_sha256'] ?? null)) {
            throw new \RuntimeException(
                'Extension/theme/environment/pending-migration compatibility snapshot changed after planning; create a new plan.',
            );
        }

        $migrationSafety = $this->migrationSafety->assess(
            (array) ($assessment['pending_migrations'] ?? []),
        );
        if ((bool) config('nexora-upgrade.block_destructive_pending_migrations', true)
            && ($migrationSafety['status'] ?? null) !== 'pass') {
            throw new \RuntimeException(
                'Pending migration safety policy is no longer PASS; create a new plan.',
            );
        }
        if (($plan['migration_safety']['migration_safety_sha256'] ?? null)
            !== ($migrationSafety['migration_safety_sha256'] ?? null)) {
            throw new \RuntimeException(
                'Pending migration safety fingerprint changed after planning; create a new plan.',
            );
        }

        $migrationBefore = (array) ($plan['migration_ledger_before'] ?? []);
        if ((bool) config('nexora-upgrade.require_migration_ledger', true)) {
            $migrationBefore = $this->migrationLedger->assertUnchanged($migrationBefore);
        }

        $clusterAssessment = $this->cluster->assess(
            (string) $plan['source_version'],
            (string) $plan['target_version'],
        );
        if ((bool) config('nexora-upgrade.require_cluster_quiescence', true)
            && ($clusterAssessment['status'] ?? null) !== 'pass') {
            throw new \RuntimeException(
                'Cluster quiescence changed since planning: '
                .implode('; ', (array) ($clusterAssessment['errors'] ?? [])),
            );
        }

        $existingJournal = $this->journal->read();
        if (
            is_array($existingJournal)
            && in_array(
                (string) ($existingJournal['status'] ?? ''),
                ['running', 'recovery_required'],
                true,
            )
        ) {
            throw new \RuntimeException(
                'An unfinished upgrade transaction exists. '
                .'Run nexora:upgrade:recovery-status and resolve recovery before another apply.',
            );
        }

        $lockPath = (string) config(
            'nexora-upgrade.lock_path',
            base_path('storage/app/nexora/upgrade/upgrade.lock'),
        );
        $lockDirectory = dirname($lockPath);
        if (
            ! is_dir($lockDirectory)
            && ! mkdir($lockDirectory, 0755, true)
            && ! is_dir($lockDirectory)
        ) {
            throw new \RuntimeException('Unable to create upgrade lock directory.');
        }

        $lock = fopen($lockPath, 'c+');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new \RuntimeException('Another Nexora upgrade process is already active.');
        }

        $maintenance = false;
        $trafficRestored = false;
        $leaseOwned = false;
        $clusterLeaseOwned = false;
        $nodeMaintenance = false;
        $warnings = [];

        $upgradeId = (string) ($plan['upgrade_id'] ?? '');
        $sourceVersion = (string) ($plan['source_version'] ?? '');
        $targetVersion = (string) ($plan['target_version'] ?? config('nexora.version'));

        try {
            $distributedLease = $this->cluster->acquire(
                $upgradeId,
                $sourceVersion,
                $targetVersion,
            );
            $clusterLeaseOwned = true;

            $lease = $this->maintenanceLease->acquire($upgradeId);
            $leaseOwned = true;

            $this->journal->begin([
                'upgrade_id' => $upgradeId,
                'source_version' => $sourceVersion,
                'target_version' => $targetVersion,
                'trusted_update_receipt_sha256' => $trustedUpdate['receipt_sha256'] ?? null,
                'backup_type' => $plan['backup']['type'] ?? null,
                'backup_reference' => $plan['backup']['reference'] ?? null,
                'backup_sha256' => $plan['backup']['checksum'] ?? null,
                'backup_database_fingerprint' => $plan['backup']['database_fingerprint'] ?? null,
                'backup_storage_fingerprint' => $plan['backup']['storage_data_plane_fingerprint'] ?? null,
                'restore_plan_id' => $plan['backup']['restore_plan_id'] ?? null,
                'restore_readiness_sha256' => $plan['backup']['restore_readiness_sha256'] ?? null,
                'maintenance_lease_sha256' => $lease['lease_sha256'] ?? null,
                'cluster_lease_sha256' => $distributedLease['cluster_sha256'] ?? null,
                'migration_ledger_before_sha256' => $migrationBefore['ledger_sha256'] ?? null,
                'compatibility_assessment_sha256' => $assessment['assessment_sha256'] ?? null,
                'migration_safety_sha256' => $migrationSafety['migration_safety_sha256'] ?? null,
                'target_deployment_generation' => $deployment['generation'] ?? null,
                'target_frontend_manifest_sha256' => $deployment['frontend_manifest_sha256'] ?? null,
                'target_runtime_environment_fingerprint' => $runtimeEnvironment['fingerprint'] ?? null,
                'target_app_key_fingerprint' => $runtimeEnvironment['active_key_fingerprint'] ?? null,
                'source_activation_epoch' => $runtimeActivation['activation_epoch'] ?? null,
                'source_runtime_activation_fingerprint' => $runtimeActivation['activation_fingerprint'] ?? null,
                'runtime_resource_fingerprint' => $resourceEnvelope['fingerprint'] ?? null,
                'resource_deep_probe_sha256' => $resourceEnvelope['deep']['deep_sha256'] ?? null,
            ]);

            $plan['status'] = 'running';
            $plan['started_at'] = now()->toIso8601String();
            $this->plans->write($plan);

            $this->cluster->enterMaintenance();
            $nodeMaintenance = true;

            $quiescence = $this->cluster->waitForCurrentQuiescence();
            $this->journal->checkpoint('runtime_quiesced', [
                'quiescence_sha256' => $quiescence['quiescence_sha256'] ?? null,
            ]);

            if (Artisan::call('down') !== 0) {
                throw new \RuntimeException('Unable to enter maintenance mode for Nexora upgrade.');
            }

            $maintenance = true;
            $this->journal->checkpoint('maintenance_enabled', [
                'maintenance_lease_sha256' => $lease['lease_sha256'] ?? null,
                'cluster_lease_sha256' => $distributedLease['cluster_sha256'] ?? null,
            ]);

            $this->maintenanceLease->verify($upgradeId);
            $this->cluster->verifyAndRenew($upgradeId, $sourceVersion, $targetVersion);

            $this->journal->checkpoint('migrations_started', [
                'migration_ledger_before_sha256' => $migrationBefore['ledger_sha256'] ?? null,
            ]);

            if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new \RuntimeException('Laravel migrations failed during Nexora upgrade.');
            }
            $this->journal->checkpoint('migrations_completed');

            $migrationAfter = $this->migrationLedger->assertConverged($migrationBefore);
            $databaseAfter = $this->databaseDataPlane->current(true);
            if (($databaseAfter['fingerprint'] ?? null) !== ($databaseDataPlane['fingerprint'] ?? null)) {
                throw new \RuntimeException(
                    'Database server/session data-plane fingerprint changed during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('database_schema_attested', [
                'database_data_plane_fingerprint' => $databaseAfter['fingerprint'] ?? null,
                'database_schema_before_sha256' => $databaseDataPlane['schema_fingerprint'] ?? null,
                'database_schema_after_sha256' => $databaseAfter['schema_fingerprint'] ?? null,
            ]);

            $storageAfter = $this->storageDataPlane->current(true);
            if (
                ($storageAfter['fingerprint'] ?? null) !== ($storageDataPlane['fingerprint'] ?? null)
                || ($storageAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Persistent storage data-plane/deep probe changed or failed during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('storage_data_plane_attested', [
                'runtime_storage_fingerprint' => $storageAfter['fingerprint'] ?? null,
                'storage_deep_probe_sha256' => $storageAfter['deep']['deep_sha256'] ?? null,
            ]);

            $serviceAfter = $this->serviceDataPlane->current(true);
            if (
                ($serviceAfter['fingerprint'] ?? null) !== ($serviceDataPlane['fingerprint'] ?? null)
                || ($serviceAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Cache/session/queue/network service data-plane changed or deep probe failed during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('service_data_plane_attested', [
                'runtime_service_fingerprint' => $serviceAfter['fingerprint'] ?? null,
                'service_deep_probe_sha256' => $serviceAfter['deep']['deep_sha256'] ?? null,
            ]);

            $hostAfter = $this->hostClock->current(true);
            if (
                ($hostAfter['fingerprint'] ?? null) !== ($hostClock['fingerprint'] ?? null)
                || ($hostAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Host/platform/clock profile changed or deep clock probe failed during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('host_clock_attested', [
                'runtime_host_fingerprint' => $hostAfter['fingerprint'] ?? null,
                'host_clock_deep_probe_sha256' => $hostAfter['deep']['deep_sha256'] ?? null,
                'clock_skew_ms' => $hostAfter['deep']['details']['clock']['skew_ms'] ?? null,
            ]);

            $resourceAfter = $this->resources->current(true);
            if (
                ($resourceAfter['fingerprint'] ?? null) !== ($resourceEnvelope['fingerprint'] ?? null)
                || ($resourceAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Runtime resource/capacity envelope changed or fell below policy during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('resource_envelope_attested', [
                'runtime_resource_fingerprint' => $resourceAfter['fingerprint'] ?? null,
                'resource_deep_probe_sha256' => $resourceAfter['deep']['deep_sha256'] ?? null,
            ]);

            $policyAfter = $this->policyPlane->current(true);
            if (
                ($policyAfter['fingerprint'] ?? null) !== ($policyPlane['fingerprint'] ?? null)
                || ($policyAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Effective runtime policy plane changed or became unsafe during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('policy_plane_attested', [
                'runtime_policy_fingerprint' => $policyAfter['fingerprint'] ?? null,
                'runtime_policy_deep_sha256' => $policyAfter['deep']['deep_sha256'] ?? null,
            ]);

            $processAfter = $this->processPlane->policy();
            if (
                ($processAfter['fingerprint'] ?? null) !== ($processPlane['fingerprint'] ?? null)
                || ($processAfter['status'] ?? null) !== 'pass'
            ) {
                throw new \RuntimeException(
                    'Runtime process-role policy changed or became unsafe during the upgrade transaction; '
                    .'keep maintenance mode enabled and investigate.',
                );
            }
            $this->journal->checkpoint('process_plane_attested', [
                'runtime_process_fingerprint' => $processAfter['fingerprint'] ?? null,
            ]);

            $this->journal->checkpoint('migration_ledger_converged', [
                'migration_ledger_after_sha256' => $migrationAfter['ledger_sha256'] ?? null,
                'migration_convergence_sha256' => $migrationAfter['convergence_sha256'] ?? null,
            ]);

            $this->cluster->verifyAndRenew($upgradeId, $sourceVersion, $targetVersion);

            if (Artisan::call('nexora:runtime:sync') !== 0) {
                throw new \RuntimeException('Runtime synchronization failed during Nexora upgrade.');
            }
            $this->journal->checkpoint('runtime_sync_completed');

            if (Artisan::call('nexora:runtime:cache') !== 0) {
                throw new \RuntimeException('Runtime cache compilation failed during Nexora upgrade.');
            }
            $this->journal->checkpoint('runtime_cache_completed');
            Artisan::call('optimize:clear');

            $this->cluster->verifyAndRenew($upgradeId, $sourceVersion, $targetVersion);

            $preHealth = $this->health->assertHealthy(
                'pre_metadata_commit',
                $sourceVersion,
                $targetVersion,
            );
            $this->journal->checkpoint('post_upgrade_health_passed', [
                'health_sha256' => $preHealth['health_sha256'],
            ]);

            $receipt = is_array($trustedUpdate['receipt'] ?? null)
                ? $trustedUpdate['receipt']
                : [];
            $installedBefore = $this->installation->metadata() ?? [];

            $runtimeActivation = $this->activation->rotate(
                'upgrade:'.$upgradeId,
                'nexora-upgrade-orchestrator',
            );
            $this->journal->checkpoint('runtime_activation_rotated', [
                'activation_epoch' => $runtimeActivation['activation_epoch'] ?? null,
                'runtime_activation_fingerprint' => $runtimeActivation['activation_fingerprint'] ?? null,
                'framework_cache_sha256' => $runtimeActivation['framework_cache']['snapshot_sha256'] ?? null,
            ]);

            $this->journal->checkpoint('installation_metadata_committing');
            $dependencyMetadata = $this->dependencyMetadata();
            $this->installation->updateMetadata([
                'previous_version' => $sourceVersion,
                'version' => $targetVersion,
                'last_upgrade_id' => $upgradeId,
                'upgraded_at' => now()->toIso8601String(),
                'previous_release_seal_sha256' => $receipt['previous_installed_release_seal_sha256'] ?? null,
                'release_seal_sha256' => $receipt['seal_sha256']
                    ?? ($installedBefore['release_seal_sha256'] ?? null),
                'release_signer_key_id' => $receipt['signer_key_id'] ?? null,
                'release_signer_public_key_sha256' => $receipt['signer_public_key_sha256'] ?? null,
                'update_trust_anchor_sha256' => $receipt['recipient_trust_anchor_sha256'] ?? null,
                'release_admitted_at' => $receipt['admitted_at'] ?? null,
                'deployment_generation' => $deployment['generation'] ?? null,
                'release_source_tree_sha256' => $deployment['source_tree_sha256']
                    ?? ($receipt['target_source_tree_sha256'] ?? null),
                'frontend_manifest_sha256' => $deployment['frontend_manifest_sha256']
                    ?? ($receipt['target_frontend_manifest_sha256'] ?? null),
                ...$dependencyMetadata,
                'cache_namespace' => $this->deployment->cacheNamespace(),
                'session_schema' => (int) config('nexora-runtime.deployment.session_schema', 1),
                'runtime_environment_fingerprint' => $runtimeEnvironment['fingerprint'] ?? null,
                'key_fingerprint' => $runtimeEnvironment['active_key_fingerprint']
                    ?? ($installedBefore['key_fingerprint'] ?? null),
                'last_upgrade_backup_sha256' => $plan['backup']['checksum'] ?? null,
                'last_upgrade_restore_readiness_sha256' => $plan['backup']['restore_readiness_sha256'] ?? null,
                'last_upgrade_database_fingerprint_sha256' => $plan['backup']['database_fingerprint'] ?? null,
                'database_data_plane_fingerprint' => $databaseAfter['fingerprint'] ?? null,
                'database_schema_fingerprint' => $databaseAfter['schema_fingerprint'] ?? null,
                'database_server_version' => $databaseAfter['normalized_server_version'] ?? null,
                'database_session_profile_sha256' => hash(
                    'sha256',
                    json_encode(
                        $databaseAfter['session_profile'] ?? [],
                        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                    ),
                ),
                'last_upgrade_database_schema_before_sha256' => $databaseDataPlane['schema_fingerprint'] ?? null,
                'last_upgrade_database_schema_after_sha256' => $databaseAfter['schema_fingerprint'] ?? null,
                'runtime_host_fingerprint' => $hostAfter['fingerprint'] ?? null,
                'runtime_process_fingerprint' => $processAfter['fingerprint'] ?? null,
                'last_upgrade_process_fingerprint' => $processAfter['fingerprint'] ?? null,
                'runtime_resource_fingerprint' => $resourceAfter['fingerprint'] ?? null,
                'runtime_policy_fingerprint' => $policyAfter['fingerprint'] ?? null,
                'runtime_policy_deep_sha256' => $policyAfter['deep']['deep_sha256'] ?? null,
                'last_upgrade_policy_fingerprint' => $policyAfter['fingerprint'] ?? null,
                'resource_deep_probe_sha256' => $resourceAfter['deep']['deep_sha256'] ?? null,
                'last_upgrade_resource_fingerprint' => $resourceAfter['fingerprint'] ?? null,
                'host_clock_deep_probe_sha256' => $hostAfter['deep']['deep_sha256'] ?? null,
                'host_clock_skew_ms' => $hostAfter['deep']['details']['clock']['skew_ms'] ?? null,
                'host_os_family' => $hostAfter['materials']['os_family'] ?? PHP_OS_FAMILY,
                'host_machine_arch' => $hostAfter['materials']['machine_arch'] ?? null,
                'runtime_storage_fingerprint' => $storageAfter['fingerprint'] ?? null,
                'runtime_service_fingerprint' => $serviceAfter['fingerprint'] ?? null,
                'last_upgrade_service_fingerprint' => $serviceAfter['fingerprint'] ?? null,
                'service_deep_probe_sha256' => $serviceAfter['deep']['deep_sha256'] ?? null,
                'cache_service_store' => $serviceAfter['materials']['cache']['store'] ?? null,
                'queue_service_connection' => $serviceAfter['materials']['queue']['connection'] ?? null,
                'mail_service_default' => $serviceAfter['materials']['mail']['default'] ?? null,
                'object_storage_disk' => $storageAfter['roles']['object']['disk'] ?? null,
                'media_storage_disk' => $storageAfter['roles']['media']['disk'] ?? null,
                'backup_storage_disk' => $storageAfter['roles']['backup']['disk'] ?? null,
                'storage_deep_probe_sha256' => $storageAfter['deep']['deep_sha256'] ?? null,
                'last_upgrade_storage_fingerprint' => $storageAfter['fingerprint'] ?? null,
                'last_upgrade_pre_health_sha256' => $preHealth['health_sha256'],
                'last_upgrade_compatibility_assessment_sha256' => $assessment['assessment_sha256'] ?? null,
                'last_upgrade_migration_ledger_before_sha256' => $migrationBefore['ledger_sha256'] ?? null,
                'last_upgrade_migration_ledger_after_sha256' => $migrationAfter['ledger_sha256'] ?? null,
                'last_upgrade_migration_convergence_sha256' => $migrationAfter['convergence_sha256'] ?? null,
                'last_upgrade_cluster_preflight_sha256' => $clusterAssessment['cluster_sha256'] ?? null,
                'last_upgrade_runtime_environment_fingerprint' => $runtimeEnvironment['fingerprint'] ?? null,
                'runtime_engine_fingerprint' => $runtimeEngine['fingerprint'] ?? null,
                'php_version' => $runtimeEngine['materials']['php_version'] ?? PHP_VERSION,
                'extension_profile_sha256' => $runtimeEngine['materials']['extension_profile_sha256'] ?? null,
                'pdo_drivers_sha256' => $runtimeEngine['materials']['pdo_drivers_sha256'] ?? null,
                'last_upgrade_runtime_engine_fingerprint' => $runtimeEngine['fingerprint'] ?? null,
                'activation_epoch' => $runtimeActivation['activation_epoch'] ?? null,
                'runtime_activation_fingerprint' => $runtimeActivation['activation_fingerprint'] ?? null,
                'runtime_activation_cache_sha256' => $runtimeActivation['framework_cache']['snapshot_sha256'] ?? null,
                'runtime_activated_at' => now()->toIso8601String(),
                'last_upgrade_runtime_activation_fingerprint' => $runtimeActivation['activation_fingerprint'] ?? null,
                'last_upgrade_migration_safety_sha256' => $migrationSafety['migration_safety_sha256'] ?? null,
                'last_upgrade_runtime_quiescence_sha256' => $quiescence['quiescence_sha256'] ?? null,
            ]);

            $this->journal->checkpoint('installation_metadata_committed');

            $postHealth = $this->health->assertHealthy(
                'post_metadata_commit',
                $targetVersion,
                $targetVersion,
            );
            $this->journal->checkpoint('post_metadata_health_passed', [
                'health_sha256' => $postHealth['health_sha256'],
            ]);
            $this->installation->updateMetadata([
                'last_upgrade_post_health_sha256' => $postHealth['health_sha256'],
            ]);

            $queueRestartExit = Artisan::call('queue:restart');
            if ($queueRestartExit !== 0) {
                throw new \RuntimeException(
                    'Queue worker restart signal failed after runtime activation rotation.',
                );
            }
            $this->journal->checkpoint('queue_restart_signaled', [
                'activation_epoch' => $runtimeActivation['activation_epoch'] ?? null,
            ]);

            $this->maintenanceLease->verify($upgradeId);
            $this->cluster->verifyAndRenew($upgradeId, $sourceVersion, $targetVersion);

            if (Artisan::call('up') !== 0) {
                throw new \RuntimeException('Unable to leave maintenance mode after Nexora upgrade.');
            }

            $maintenance = false;
            $trafficRestored = true;

            try {
                $this->cluster->activateCurrent();
                $nodeMaintenance = false;
            } catch (\Throwable $exception) {
                $warnings[] = 'Current runtime node activation needs operator review: '
                    .$exception->getMessage();
            }

            try {
                $this->cluster->release($upgradeId);
                $clusterLeaseOwned = false;
            } catch (\Throwable $exception) {
                $warnings[] = 'Distributed upgrade lease cleanup needs operator review: '
                    .$exception->getMessage();
            }

            try {
                $this->journal->checkpoint('maintenance_disabled');
            } catch (\Throwable $exception) {
                $warnings[] = 'Transaction checkpoint after traffic restore failed: '
                    .$exception->getMessage();
            }

            try {
                $this->maintenanceLease->release($upgradeId);
                $leaseOwned = false;
            } catch (\Throwable $exception) {
                $warnings[] = 'Maintenance lease cleanup needs operator review: '
                    .$exception->getMessage();
            }

            // Everything below is post-commit bookkeeping. A bookkeeping warning
            // must never relabel a healthy live target as a destructive recovery incident.
            $plan['status'] = 'completed';
            $plan['completed_at'] = now()->toIso8601String();
            $history = null;
            $transactionHistory = null;

            try {
                $this->plans->write($plan);
                $history = $this->plans->archive($plan, 'completed');
                $this->plans->clear();
            } catch (\Throwable $exception) {
                $warnings[] = 'Completed upgrade plan archival needs operator review: '
                    .$exception->getMessage();
            }

            try {
                $this->trustedUpdate->clear();
            } catch (\Throwable $exception) {
                $warnings[] = 'Trusted update admission cleanup needs operator review: '
                    .$exception->getMessage();
            }

            try {
                $this->journal->complete();
                $transactionHistory = $this->journal->archiveAndClear();
            } catch (\Throwable $exception) {
                $warnings[] = 'Completed upgrade transaction archival needs operator review: '
                    .$exception->getMessage();
            }

            return [
                'status' => 'completed',
                'upgrade_id' => $upgradeId,
                'source_version' => $sourceVersion,
                'target_version' => $targetVersion,
                'history' => $history,
                'transaction_history' => $transactionHistory,
                'warnings' => $warnings,
                'pre_health_sha256' => $preHealth['health_sha256'],
                'post_health_sha256' => $postHealth['health_sha256'],
                'migration_ledger_before_sha256' => $migrationBefore['ledger_sha256'] ?? null,
                'migration_ledger_after_sha256' => $migrationAfter['ledger_sha256'] ?? null,
                'migration_convergence_sha256' => $migrationAfter['convergence_sha256'] ?? null,
                'cluster_preflight_sha256' => $clusterAssessment['cluster_sha256'] ?? null,
                'migration_safety_sha256' => $migrationSafety['migration_safety_sha256'] ?? null,
                'runtime_quiescence_sha256' => $quiescence['quiescence_sha256'] ?? null,
                'deployment_generation' => $deployment['generation'] ?? null,
                'frontend_manifest_sha256' => $deployment['frontend_manifest_sha256'] ?? null,
                'runtime_environment_fingerprint' => $runtimeEnvironment['fingerprint'] ?? null,
                'activation_epoch' => $runtimeActivation['activation_epoch'] ?? null,
                'runtime_activation_fingerprint' => $runtimeActivation['activation_fingerprint'] ?? null,
                'runtime_activation_cache_sha256' => $runtimeActivation['framework_cache']['snapshot_sha256'] ?? null,
                'runtime_engine_fingerprint' => $runtimeEngine['fingerprint'] ?? null,
                'php_version' => $runtimeEngine['materials']['php_version'] ?? PHP_VERSION,
                'extension_profile_sha256' => $runtimeEngine['materials']['extension_profile_sha256'] ?? null,
                'pdo_drivers_sha256' => $runtimeEngine['materials']['pdo_drivers_sha256'] ?? null,
                'database_data_plane_fingerprint' => $databaseAfter['fingerprint'] ?? null,
                'database_schema_before_sha256' => $databaseDataPlane['schema_fingerprint'] ?? null,
                'database_schema_after_sha256' => $databaseAfter['schema_fingerprint'] ?? null,
                'runtime_storage_fingerprint' => $storageAfter['fingerprint'] ?? null,
                'storage_deep_probe_sha256' => $storageAfter['deep']['deep_sha256'] ?? null,
                'runtime_service_fingerprint' => $serviceAfter['fingerprint'] ?? null,
                'service_deep_probe_sha256' => $serviceAfter['deep']['deep_sha256'] ?? null,
                'runtime_host_fingerprint' => $hostAfter['fingerprint'] ?? null,
                'runtime_process_fingerprint' => $processAfter['fingerprint'] ?? null,
                'last_upgrade_process_fingerprint' => $processAfter['fingerprint'] ?? null,
                'runtime_resource_fingerprint' => $resourceAfter['fingerprint'] ?? null,
                'resource_deep_probe_sha256' => $resourceAfter['deep']['deep_sha256'] ?? null,
                'runtime_policy_fingerprint' => $policyAfter['fingerprint'] ?? null,
                'runtime_policy_deep_sha256' => $policyAfter['deep']['deep_sha256'] ?? null,
                'host_clock_deep_probe_sha256' => $hostAfter['deep']['deep_sha256'] ?? null,
                'host_clock_skew_ms' => $hostAfter['deep']['details']['clock']['skew_ms'] ?? null,
            ];
        } catch (\Throwable $exception) {
            if ($trafficRestored) {
                $warnings[] = 'Post-commit finalization warning: '.$exception->getMessage();

                return [
                    'status' => 'completed_with_warning',
                    'upgrade_id' => $upgradeId,
                    'warnings' => $warnings,
                ];
            }

            $plan['status'] = 'failed';
            $plan['failed_at'] = now()->toIso8601String();
            $plan['failure'] = $exception->getMessage();

            if ($maintenance) {
                try {
                    $currentJournal = $this->journal->read();
                    $this->journal->fail(
                        (string) ($currentJournal['stage'] ?? 'apply_failed'),
                        $exception->getMessage(),
                    );
                } catch (\Throwable) {
                    // Recovery journaling must not hide the original upgrade failure.
                }

                try {
                    $this->cluster->holdForRecovery($upgradeId, $exception->getMessage());
                } catch (\Throwable) {
                    // The active journal still preserves the original failure.
                }
            } else {
                try {
                    $currentJournal = $this->journal->read();
                    if (
                        is_array($currentJournal)
                        && ($currentJournal['status'] ?? null) === 'running'
                    ) {
                        $this->journal->abortPreMutation(
                            (string) ($currentJournal['stage'] ?? 'pre_mutation_aborted'),
                            $exception->getMessage(),
                        );
                        $this->journal->archiveAndClear();
                    }
                } catch (\Throwable) {
                    // Preserve the original exception when cleanup itself fails.
                }
            }

            try {
                $this->plans->write($plan);
                $this->plans->archive($plan, 'failed');
            } catch (\Throwable) {
                // Failure evidence in the transaction journal remains authoritative.
            }

            throw $exception;
        } finally {
            if (! $maintenance && ! $trafficRestored) {
                if ($nodeMaintenance) {
                    try {
                        $this->cluster->activateCurrent();
                    } catch (\Throwable) {
                        // Best-effort pre-mutation cleanup only.
                    }
                }

                if ($clusterLeaseOwned) {
                    try {
                        $this->cluster->release($upgradeId);
                    } catch (\Throwable) {
                        // Best-effort pre-mutation cleanup only.
                    }
                }

                if ($leaseOwned) {
                    try {
                        $this->maintenanceLease->release($upgradeId);
                    } catch (\Throwable) {
                        // Best-effort pre-mutation cleanup only.
                    }
                }
            }

            if (! $maintenance) {
                @unlink($lockPath);
            }

            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $deployment
     * @param array<string,mixed> $runtimeEnvironment
     * @param array<string,mixed> $runtimeActivation
     * @param array<string,mixed> $runtimeEngine
     * @param array<string,mixed> $databaseDataPlane
     * @param array<string,mixed> $storageDataPlane
     * @param array<string,mixed> $serviceDataPlane
     * @param array<string,mixed> $hostClock
     * @param array<string,mixed> $resourceEnvelope
     * @param array<string,mixed> $policyPlane
     * @param array<string,mixed> $processPlane
     * @param array<string,mixed> $framework
     * @param array<string,mixed> $dependencies
     */
    private function assertPlanRuntimeIdentity(
        array $plan,
        array $deployment,
        array $runtimeEnvironment,
        array $runtimeActivation,
        array $runtimeEngine,
        array $databaseDataPlane,
        array $storageDataPlane,
        array $serviceDataPlane,
        array $hostClock,
        array $resourceEnvelope,
        array $policyPlane,
        array $processPlane,
        array $framework,
        array $dependencies,
    ): void {
        $checks = [
            [
                'matches' => ($plan['target_runtime_engine']['fingerprint'] ?? null)
                    === ($runtimeEngine['fingerprint'] ?? null),
                'message' => 'PHP runtime engine/extension fingerprint changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_runtime_activation']['activation_fingerprint'] ?? null)
                    === ($runtimeActivation['activation_fingerprint'] ?? null)
                    && ($plan['source_runtime_activation']['activation_epoch'] ?? null)
                    === ($runtimeActivation['activation_epoch'] ?? null),
                'message' => 'Runtime activation/cache fingerprint changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['target_deployment']['generation'] ?? null)
                    === ($deployment['generation'] ?? null),
                'message' => 'Target deployment generation changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['target_runtime_environment']['fingerprint'] ?? null)
                    === ($runtimeEnvironment['fingerprint'] ?? null),
                'message' => 'Target runtime environment fingerprint changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_database_data_plane']['fingerprint'] ?? null)
                    === ($databaseDataPlane['fingerprint'] ?? null)
                    && ($plan['source_database_data_plane']['schema_fingerprint'] ?? null)
                    === ($databaseDataPlane['schema_fingerprint'] ?? null),
                'message' => 'Database data-plane/schema fingerprint changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_storage_data_plane']['fingerprint'] ?? null)
                    === ($storageDataPlane['fingerprint'] ?? null),
                'message' => 'Persistent storage data-plane fingerprint changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_service_data_plane']['fingerprint'] ?? null)
                    === ($serviceDataPlane['fingerprint'] ?? null)
                    && ($serviceDataPlane['status'] ?? null) === 'pass',
                'message' => 'Service data-plane fingerprint changed or its deep probe failed after planning.',
            ],
            [
                'matches' => ($plan['source_host_clock']['fingerprint'] ?? null)
                    === ($hostClock['fingerprint'] ?? null)
                    && ($hostClock['status'] ?? null) === 'pass',
                'message' => 'Host/platform/timezone/locale/clock profile changed or its deep probe failed after planning.',
            ],
            [
                'matches' => ($plan['source_resource_envelope']['fingerprint'] ?? null)
                    === ($resourceEnvelope['fingerprint'] ?? null)
                    && ($resourceEnvelope['status'] ?? null) === 'pass',
                'message' => 'Runtime resource/capacity envelope changed or its deep probe failed after planning.',
            ],
            [
                'matches' => ($plan['source_policy_plane']['fingerprint'] ?? null)
                    === ($policyPlane['fingerprint'] ?? null)
                    && ($policyPlane['status'] ?? null) === 'pass',
                'message' => 'Effective runtime policy plane changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_process_plane']['fingerprint'] ?? null)
                    === ($processPlane['fingerprint'] ?? null)
                    && ($processPlane['status'] ?? null) === 'pass',
                'message' => 'Runtime process-role policy changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_dependency_state']['fingerprint'] ?? null)
                    === ($dependencies['fingerprint'] ?? null)
                    && ($dependencies['status'] ?? null) === 'pass',
                'message' => 'Reviewed dependency state changed after the upgrade plan was sealed.',
            ],
            [
                'matches' => ($plan['source_dependency_state']['laravel_framework_version'] ?? null)
                    === ($framework['installed_version'] ?? null),
                'message' => 'Installed Laravel framework version changed after the upgrade plan was sealed.',
            ],
        ];

        foreach ($checks as $check) {
            if ($check['matches']) {
                continue;
            }

            throw new \RuntimeException($check['message'].' Create a new plan before applying migrations.');
        }
    }

    /**
     * @param list<string> $errors
     * @param array<string,mixed> $trustedUpdate
     * @param array<string,mixed> $backup
     * @param array<string,mixed> $migrationLedger
     * @param array<string,mixed> $migrationSafety
     * @param array<string,mixed> $cluster
     * @param array<string,mixed> $databaseDataPlane
     * @param array<string,mixed> $storageDataPlane
     * @param array<string,mixed> $serviceDataPlane
     * @param array<string,mixed> $hostClock
     * @param array<string,mixed> $resourceEnvelope
     * @param array<string,mixed> $policyPlane
     * @param array<string,mixed> $processPlane
     * @param array<string,mixed> $framework
     * @param array<string,mixed> $dependencies
     */
    private function appendPlanSafetyErrors(
        array &$errors,
        array $trustedUpdate,
        array $backup,
        array $migrationLedger,
        array $migrationSafety,
        array $cluster,
        array $databaseDataPlane,
        array $storageDataPlane,
        array $serviceDataPlane,
        array $hostClock,
        array $resourceEnvelope,
        array $policyPlane,
        array $processPlane,
        array $framework,
        array $dependencies,
    ): void {
        if ($this->keyRotation->read() !== null) {
            $errors[] = 'An APP_KEY rotation receipt is active. Commit or abort key rotation before planning a code/schema upgrade.';
        }

        foreach ((array) ($trustedUpdate['errors'] ?? []) as $error) {
            $errors[] = 'Trusted update: '.$error;
        }

        if ((bool) config('nexora-upgrade.require_backup', true) && ! ($backup['ok'] ?? false)) {
            $errors[] = 'A verified source-version backup is required before an in-place upgrade.';
        }
        if ((bool) config('nexora-upgrade.require_restore_readiness', true)
            && ! ($backup['restore_ready'] ?? false)) {
            $errors[] = 'A guarded restore-readiness plan bound to the verified backup is required before upgrade.';
        }
        if ((bool) config('nexora-upgrade.block_preexisting_maintenance', true)
            && app()->isDownForMaintenance()) {
            $errors[] = 'The application is already in maintenance mode; upgrade maintenance ownership is ambiguous.';
        }
        if ((bool) config('nexora-upgrade.require_migration_ledger', true)
            && ($migrationLedger['duplicates'] ?? []) !== []) {
            $errors[] = 'Migration ledger contains duplicate migration names and cannot be sealed safely.';
        }

        if ((bool) config('nexora-database-runtime.require_schema_attestation', true)
            && ! $this->hasSha256($databaseDataPlane['schema_fingerprint'] ?? null)) {
            $errors[] = 'Database structural schema attestation is unavailable and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-storage-runtime.require_exact_data_plane', true)
            && ! $this->identityIsReady($storageDataPlane)) {
            $errors[] = 'Persistent storage data-plane/deep probe is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-network-runtime.require_exact_service_data_plane', true)
            && ! $this->identityIsReady($serviceDataPlane)) {
            $errors[] = 'Cache/session/queue/network service data-plane deep probe is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-host-runtime.require_exact_host_profile', true)
            && ! $this->identityIsReady($hostClock)) {
            $errors[] = 'Host/platform/clock deep probe is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-resource-runtime.require_deep_capacity_for_upgrade', true)
            && ! $this->identityIsReady($resourceEnvelope)) {
            $errors[] = 'Runtime resource/capacity envelope deep probe is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-policy-runtime.require_exact_policy_plane', true)
            && ! $this->identityIsReady($policyPlane)) {
            $errors[] = 'Effective runtime policy plane is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if ((bool) config('nexora-process-runtime.require_exact_process_policy', true)
            && ! $this->identityIsReady($processPlane)) {
            $errors[] = 'Runtime process-role policy is not PASS and the upgrade plan cannot be sealed safely.';
        }
        if (($framework['status'] ?? 'fail') !== 'pass') {
            $errors[] = 'Installed Laravel framework is outside the certified Nexora 13.x compatibility range.';
        }
        if (($dependencies['status'] ?? 'fail') !== 'pass') {
            $errors[] = 'Reviewed dependency-lock state is not PASS; refresh/review locks before planning an upgrade.';
        }

        if ((bool) config('nexora-upgrade.block_destructive_pending_migrations', true)
            && ($migrationSafety['status'] ?? null) !== 'pass') {
            $findings = array_map(
                static fn (array $finding): string => (string) ($finding['migration'] ?? 'unknown')
                    .':'.(string) ($finding['rule'] ?? 'unknown'),
                (array) ($migrationSafety['findings'] ?? []),
            );
            $errors[] = 'Pending migration safety policy rejected destructive/ambiguous up() operations: '
                .implode(',', array_values(array_unique($findings)));
        }

        if ((bool) config('nexora-upgrade.require_cluster_quiescence', true)
            && ($cluster['status'] ?? null) !== 'pass') {
            foreach ((array) ($cluster['errors'] ?? []) as $error) {
                $errors[] = 'Cluster coordination: '.$error;
            }
        }
    }

    /** @param array<string,mixed> $identity */
    private function identityIsReady(array $identity): bool
    {
        return ($identity['status'] ?? null) === 'pass'
            && $this->hasSha256($identity['fingerprint'] ?? null);
    }

    private function hasSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }

    /** @return array<string, mixed> */
    private function dependencyMetadata(): array
    {
        $framework = $this->frameworkCompatibility->assertCompatible();
        $dependencies = $this->reviewedDependencies->inspect();

        if (($dependencies['status'] ?? 'fail') !== 'pass') {
            throw new \RuntimeException('Reviewed dependency-lock state changed during upgrade finalization.');
        }

        $hashes = (array) ($dependencies['hashes'] ?? []);

        return [
            'composer_lock_sha256' => $hashes['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $hashes['package_lock_sha256'] ?? null,
            'runtime_dependency_fingerprint' => $dependencies['fingerprint'] ?? null,
            'laravel_framework_version' => $framework['installed_version'] ?? null,
        ];
    }

}
