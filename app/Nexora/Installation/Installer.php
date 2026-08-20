<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Models\Role;
use App\Models\DataConnection;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use App\Nexora\Foundation\Runtime\FrameworkCompatibility;
use App\Nexora\Foundation\Runtime\FreshInstallDependencyTrust;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Data\ConnectionCatalog;
use App\Nexora\Data\ConnectionTester;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class Installer
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public const PROTOCOL = 'v5.29';

    public const SOURCE_GENERATION = 'n1-v5.29';

    private ?string $activeRunId = null;

    public function __construct(
        private SystemRequirementChecker $requirements,
        private EnvironmentWriter $environment,
        private DatabaseProvisioner $database,
        private DatabaseBackupManager $backups,
        private InstallationState $state,
        private RuntimeSynchronizer $runtime,
        private InstallationRunControl $runControl,
        private ConnectionCatalog $connectionCatalog,
        private ConnectionTester $connectionTester,
        private RuntimeDeploymentIdentity $deployment,
        private RuntimeActivationIdentity $activation,
        private RuntimeEnvironmentIdentity $runtimeEnvironment,
        private RuntimeEngineIdentity $runtimeEngine,
        private DatabaseDataPlaneIdentity $databaseDataPlane,
        private RuntimeStorageDataPlaneIdentity $storageDataPlane,
        private RuntimeServiceDataPlaneIdentity $serviceDataPlane,
        private RuntimeHostClockIdentity $hostClock,
        private RuntimeResourceEnvelopeIdentity $resources,
        private RuntimePolicyPlaneIdentity $policyPlane,
        private RuntimeProcessPlane $processPlane,
        private TenantContext $tenantContext,
        private FrameworkCompatibility $frameworkCompatibility,
        private FreshInstallDependencyTrust $installDependencyTrust,
        private SourceActivationIdentity $sourceActivation,
        private RuntimeInstallationReadiness $installationReadiness,
        private RuntimePostInstallHandoff $postInstallHandoff,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function install(array $input, ?callable $progress = null): void
    {
        $this->assertInstallerIsOpen();
        $source = $this->sourceActivation->assertCurrent();

        $this->activeRunId = isset($input['_installation_run_id']) ? (string) $input['_installation_run_id'] : null;
        $this->checkpoint('preflight', true);
        $this->report($progress, 'preflight', 'Installation preflight', 'running', 2, sprintf(
                'Checking installation state with %s · %s · %s.',
                (string) ($source['platform_version'] ?? 'unknown'),
                (string) ($source['running_protocol'] ?? self::PROTOCOL),
                (string) ($source['running_generation'] ?? self::SOURCE_GENERATION),
            ));

        $mutexPath = (string) config('installer.mutex_path', base_path('storage/app/nexora/installing.lock'));
        if (! is_dir(dirname($mutexPath)) && ! mkdir(dirname($mutexPath), 0755, true) && ! is_dir(dirname($mutexPath))) {
            throw new RuntimeException('Unable to prepare the installer lock directory.');
        }
        $mutex = fopen($mutexPath, 'c+');
        if ($mutex === false || ! flock($mutex, LOCK_EX | LOCK_NB)) {
            if (is_resource($mutex)) { fclose($mutex); }
            throw new RuntimeException('Another Nexora installation is already running.');
        }

        try {
            $this->assertInstallerIsOpen();
            $this->installDependencyTrust->discardOrphanedBootstrapReceipt();

            $preflight = $this->requirements->check();
            if (! $preflight['ready']) {
                throw new RuntimeException('Server requirements are not satisfied. Resolve blocking checks before installation.');
            }

            // Resolve deterministic dependency trust before any database mutation.
            // A missing human review may bootstrap when exact locks/runtime match;
            // invalid or stale dependency evidence still fails closed here.
            $dependencyTrust = $this->installDependencyTrust->resolve();
            $dependencyTrustMode = (string) ($dependencyTrust['trust_mode'] ?? 'unknown');
            $this->report(
                $progress,
                'preflight',
                'Installation preflight',
                'completed',
                8,
                sprintf(
                    'Server requirements and installer lock are ready. Runtime dependency trust: %s. Installer protocol: %s.',
                    $dependencyTrustMode,
                    self::PROTOCOL,
                ),
            );

            $this->checkpoint('database', true);
            $this->report($progress, 'database', 'Database verification', 'running', 10, 'Verifying the selected database driver, connection and schema safety.');
            $db = [
                'driver' => (string) $input['db_driver'],
                'host' => (string) ($input['db_host'] ?? ''),
                'port' => (int) ($input['db_port'] ?? 0),
                'database' => (string) $input['db_database'],
                'username' => (string) ($input['db_username'] ?? ''),
                'password' => (string) ($input['db_password'] ?? ''),
            ];
            $test = $this->database->test($db, (bool) ($input['db_create'] ?? false));
            if (! $test['ok']) {
                throw new RuntimeException('Database connection failed: '.$test['message']);
            }
            if ($this->activeRunId !== null) {
                $this->runControl->bindDatabaseTarget($this->activeRunId, $db);
            }

            // Resolve every installation-safe runtime attestation before any
            // destructive reset, migration or seeding. Strict C2/C6 production
            // certification remains stronger and is reported as pending warnings.
            $this->applyDatabaseRuntimeConfig($db);
            $this->checkpoint('runtime-readiness', true);
            $this->report(
                $progress,
                'runtime-readiness',
                'Runtime readiness preflight',
                'running',
                14,
                'Checking source/deployment, dependency, host/clock, resource, policy, process and activation safety before database mutation.',
            );
            $runtimeReadiness = $this->installationReadiness->assertReady($dependencyTrust);
            $hostPreflight = (array) ($runtimeReadiness['details']['host_clock'] ?? []);
            $this->report(
                $progress,
                'runtime-readiness',
                'Runtime readiness preflight',
                'completed',
                16,
                sprintf(
                    'Installer-safe runtime readiness passed %d/%d components. %s',
                    (int) ($runtimeReadiness['components_passed'] ?? 0),
                    (int) ($runtimeReadiness['components_total'] ?? 0),
                    $this->hostClockProgressMessage($hostPreflight),
                ),
            );

            $this->checkpoint('database', true);
            $tableCount = (int) ($test['object_count'] ?? $test['table_count'] ?? 0);
            $recovery = $tableCount > 0 ? $this->runControl->recoveryForDatabase($db) : null;
            $recoverable = ($recovery['resume_compatible'] ?? false) === true ? $recovery : null;
            $existingAction = (string) ($input['db_existing_action'] ?? '');
            $resumeInterrupted = $tableCount > 0
                && $recoverable !== null
                && $existingAction !== 'reset';

            if ($tableCount > 0 && $existingAction === 'resume' && $recovery !== null && $recoverable === null) {
                $reason = (string) ($recovery['resume_reason'] ?? '');
                if ($reason === '') {
                    $reason = 'The interrupted installation cannot be safely resumed by this installer generation. '
                        .'Start clean with backup or explicit overwrite consent.';
                }
                throw new RuntimeException($reason);
            }

            $databaseInstallAction = $tableCount === 0 ? 'clean-empty' : 'existing-database';
            $databaseProtectionMode = $tableCount === 0 ? 'not-required' : 'unknown';

            if ($resumeInterrupted) {
                $stage = (string) ($recoverable['stage'] ?? 'protected provisioning');
                $databaseInstallAction = 'resume-interrupted';
                $databaseProtectionMode = 'preserve-recoverable-schema';

                $this->report(
                    $progress,
                    'database',
                    'Database verification',
                    'completed',
                    18,
                    'Detected a Nexora-owned interrupted installation at stage '.$stage
                        .'. The operator selected safe resume; the partial schema will be resumed without a second destructive reset.',
                );
                $this->report(
                    $progress,
                    'backup',
                    'Existing database protection',
                    'completed',
                    24,
                    'Resume mode preserves the protected interrupted schema. No destructive reset was authorized.',
                );
                $this->report(
                    $progress,
                    'reset',
                    'Database recovery',
                    'completed',
                    32,
                    'Preserving the interrupted Nexora schema and continuing idempotent migrations/seeding.',
                );
            } elseif ($tableCount > 0) {
                if (! (bool) ($input['db_reset_existing'] ?? false)) {
                    throw new RuntimeException(
                        'The selected database contains existing objects. Choose Resume when available, '
                        .'or explicitly authorize a clean reset with backup/no-backup consent.',
                    );
                }

                $verifiedBackup = (bool) ($input['db_backup_confirmed'] ?? false)
                    && ! empty($input['db_backup_token']);
                $skipBackup = (bool) ($input['db_skip_backup_consent'] ?? false)
                    && hash_equals(
                        (string) $db['database'],
                        (string) ($input['db_skip_backup_database'] ?? ''),
                    );

                $this->checkpoint('backup', true);
                if ($verifiedBackup) {
                    $databaseProtectionMode = 'verified-backup';
                    $this->report(
                        $progress,
                        'backup',
                        'Existing database protection',
                        'running',
                        18,
                        'Verifying the downloaded protected backup for this installer session.',
                    );
                    $this->backups->validate(
                        (string) $input['db_backup_token'],
                        $db,
                        (string) ($input['_installer_session_id'] ?? ''),
                    );
                    $this->report(
                        $progress,
                        'backup',
                        'Existing database protection',
                        'completed',
                        22,
                        'Protected database backup verified.',
                    );
                } elseif ($skipBackup) {
                    $databaseProtectionMode = 'explicit-no-backup-consent';
                    $this->report(
                        $progress,
                        'backup',
                        'Existing database protection',
                        'completed',
                        22,
                        'You explicitly authorized a destructive reset without a Nexora backup. '
                            .'Existing data cannot be recovered by Nexora.',
                    );
                } else {
                    throw new RuntimeException(
                        'Create/download a backup, or explicitly consent to continue without a backup '
                        .'and type the database name exactly.',
                    );
                }

                $databaseInstallAction = $recovery !== null
                    ? 'discard-interrupted-and-reset'
                    : 'reset-existing';

                // From this point onward schema-changing work is intentionally non-cancellable.
                $this->checkpoint('reset', false);
                $this->report(
                    $progress,
                    'reset',
                    'Database reset',
                    'running',
                    23,
                    'Removing existing database objects after explicit reset authorization.',
                );
                $this->database->wipe($db, function (array $event) use ($progress): void {
                    $fraction = ((int) ($event['progress'] ?? 0)) / 100;
                    $percent = 23 + (int) floor(9 * $fraction);
                    $this->report(
                        $progress,
                        'reset',
                        'Database reset',
                        'running',
                        $percent,
                        sprintf(
                            'Removing %s %d of %d: %s',
                            (string) ($event['type'] ?? 'object'),
                            (int) ($event['current'] ?? 0),
                            (int) ($event['total'] ?? 0),
                            (string) ($event['name'] ?? ''),
                        ),
                    );
                });
                $this->report(
                    $progress,
                    'reset',
                    'Database reset',
                    'completed',
                    32,
                    'Existing database objects were removed. Nexora now has a clean schema target.',
                );
            } else {
                $this->report(
                    $progress,
                    'database',
                    'Database verification',
                    'completed',
                    22,
                    'Database connection verified. The selected database is empty and ready.',
                );
                $this->report(
                    $progress,
                    'backup',
                    'Existing database backup',
                    'completed',
                    24,
                    'Not required because the selected database is empty.',
                );
                $this->report(
                    $progress,
                    'reset',
                    'Database reset',
                    'completed',
                    32,
                    'Not required because the selected database is empty.',
                );
            }

            $this->checkpoint('environment', false);
            $this->report($progress, 'environment', 'Environment configuration', 'running', 34, 'Writing the production environment configuration.');
            $key = (string) config('app.key');
            if ($key === '') {
                $key = 'base64:'.base64_encode(random_bytes(32));
            }

            $environmentPath = $this->environment->write([
                'APP_NAME' => (string) $input['app_name'],
                'APP_ENV' => 'production',
                'APP_KEY' => $key,
                'APP_DEBUG' => 'false',
                'APP_URL' => rtrim((string) $input['app_url'], '/'),
                'APP_LOCALE' => (string) $input['language'],
                ...$this->database->environment($db),
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
                'QUEUE_CONNECTION' => 'database',
                'NEXORA_DEMO_ADMIN_EMAIL' => '',
                'NEXORA_DEMO_ADMIN_PASSWORD' => '',
            ]);

            $environmentMode = $environmentPath === (string) config('installer.environment_fallback_path') ? 'protected-fallback' : 'project-root';
            $bootstrapKeyPath = (string) config('installer.environment_bootstrap_key_path', '');
            if ($bootstrapKeyPath !== '' && is_file($bootstrapKeyPath)) {
                @unlink($bootstrapKeyPath);
            }
            $this->report(
                $progress,
                'environment',
                'Environment configuration',
                'completed',
                40,
                $environmentMode === 'protected-fallback'
                    ? 'Environment saved in protected Nexora storage because the project root is not writable.'
                    : 'Environment saved to the project root .env file.',
            );

            Config::set('app.name', (string) $input['app_name']);
            Config::set('app.url', rtrim((string) $input['app_url'], '/'));
            Config::set('app.locale', (string) $input['language']);
            Config::set('app.key', $key);
            Config::set('session.driver', 'database');
            Config::set('cache.default', 'database');
            Config::set('queue.default', 'database');

            // The installer is a long-lived request that booted before the new
            // .env was written. Keep every runtime identity plane on the same
            // effective configuration that the next PHP request will load.
            $this->runtimeEnvironment->forgetMemoizedIdentity();
            $this->serviceDataPlane->forgetMemoizedIdentity();
            $this->processPlane->forgetMemoizedPolicy();

            // Environment values changed inside this long-lived installer request.
            // Normalize process-global timezone/Intl state immediately so the final
            // host profile observes the same values the next PHP request will use.
            date_default_timezone_set((string) config('app.timezone', 'UTC'));
            if (class_exists(\Locale::class)) {
                \Locale::setDefault((string) $input['language']);
            }

            $this->report($progress, 'migrations', 'Database migrations', 'running', 42, 'Applying Nexora schema migrations.');
            Artisan::call('migrate', ['--force' => true]);
            $this->report($progress, 'migrations', 'Database migrations', 'completed', 58, 'Database migrations completed.', $this->artisanOutput());

            // A long-lived installer request may still hold the organization object
            // that existed before a reset/migration. The enterprise migration creates
            // the fresh default organization, so stale tenant state must not leak into
            // the seed phase.
            $this->tenantContext->clear();

            $this->report($progress, 'seed', 'Core platform data', 'running', 60, 'Creating core roles, permissions and platform settings.');
            Artisan::call('db:seed', ['--class' => NexoraCoreSeeder::class, '--force' => true]);
            $this->report($progress, 'seed', 'Core platform data', 'completed', 70, 'Core platform data seeded.', $this->artisanOutput());

            $this->report($progress, 'admin', 'Super Admin account', 'running', 72, 'Creating and assigning the protected Super Admin account.');
            $admin = User::query()->updateOrCreate(
                ['email' => strtolower((string) $input['admin_email'])],
                [
                    'name' => (string) $input['admin_name'],
                    'password' => Hash::make((string) $input['admin_password']),
                    'status' => 'active',
                    'locale' => (string) $input['language'],
                ],
            );
            // The installer proves control of this account interactively. Mark the
            // first Super Admin verified explicitly because email_verified_at is
            // intentionally not mass assignable on the User model.
            $admin->forceFill(['email_verified_at' => now()])->save();
            $superAdmin = Role::query()->where('slug', 'super-admin')->firstOrFail();
            $admin->roles()->sync([$superAdmin->id]);

            // N0.33 enterprise tenancy always has a default organization. Fresh
            // installation links the protected first Super Admin as its owner so
            // all tenant-scoped content has an explicit administrative boundary.
            if (class_exists(EnterpriseOrganization::class)) {
                $defaultOrganization = EnterpriseOrganization::query()->where('is_default', true)->first();
                if ($defaultOrganization !== null) {
                    $defaultOrganization->forceFill(['owner_user_id' => $admin->id])->save();
                    EnterpriseOrganizationMember::query()->updateOrCreate(
                        ['organization_id' => $defaultOrganization->id, 'user_id' => $admin->id],
                        ['id' => (string) Str::uuid(), 'role' => 'owner', 'status' => 'active', 'joined_at' => now()],
                    );
                }
            }

            $configuredServices = 0;
            $healthyServices = 0;
            foreach (array_values(array_unique((array) ($input['requested_data_services'] ?? []))) as $service) {
                $service = (string) $service;
                $definition = $this->connectionCatalog->get($service);
                $payload = (array) (($input['data_services'] ?? [])[$service] ?? []);
                $endpoint = trim((string) ($payload['endpoint'] ?? ''));
                $isDynamo = $service === 'aws_dynamodb';
                $hasConfiguration = $isDynamo
                    ? trim((string) ($payload['region'] ?? '')) !== ''
                    : $endpoint !== '';

                $connection = DataConnection::query()->updateOrCreate(
                    ['driver' => $service, 'purpose' => 'auxiliary'],
                    [
                        'provider' => (string) $definition['provider'],
                        'name' => (string) $definition['label'],
                        'status' => ! $hasConfiguration
                            ? 'unconfigured'
                            : (($definition['available'] ?? false) ? 'untested' : 'adapter-missing'),
                        'is_enabled' => false,
                        'endpoint' => $endpoint !== '' ? $endpoint : null,
                        'database' => trim((string) ($payload['database'] ?? '')) ?: null,
                        'username' => trim((string) ($payload['username'] ?? '')) ?: null,
                        'secret_payload' => array_filter([
                            'password' => $payload['password'] ?? null,
                            'username' => $payload['username'] ?? null,
                            'key' => $payload['access_key'] ?? null,
                            'secret' => $payload['secret_key'] ?? null,
                        ], static fn ($value): bool => $value !== null && $value !== ''),
                        'options' => array_filter(['region' => $payload['region'] ?? null]),
                        'last_tested_at' => null,
                        'last_error' => null,
                    ],
                );

                if (! $hasConfiguration) {
                    continue;
                }
                $configuredServices++;
                if (! ($definition['available'] ?? false)) {
                    $connection->forceFill(['last_error' => (string) $definition['availability_message']])->save();
                    continue;
                }

                $result = $this->connectionTester->test($connection);
                $connection->forceFill([
                    'status' => $result['ok'] ? 'healthy' : 'failed',
                    'is_enabled' => (bool) $result['ok'],
                    'last_tested_at' => now(),
                    'last_error' => $result['ok'] ? null : $result['message'],
                ])->save();
                if ($result['ok']) {
                    $healthyServices++;
                }
            }
            $serviceSummary = $configuredServices > 0
                ? sprintf(' Auxiliary data services: %d configured, %d healthy/enabled.', $configuredServices, $healthyServices)
                : '';
            $this->report($progress, 'admin', 'Super Admin account', 'completed', 78, 'Super Admin account is verified and ready.'.$serviceSummary);

            $this->report($progress, 'runtime', 'Nexora runtime', 'running', 80, 'Synchronizing module and capability runtime metadata.');
            $sync = $this->runtime->sync();
            $this->report($progress, 'runtime', 'Nexora runtime', 'running', 87, 'Compiling the Nexora runtime cache.');
            Artisan::call('nexora:runtime:cache');
            $this->report($progress, 'runtime', 'Nexora runtime', 'completed', 92, 'Runtime synchronization and cache compilation completed.', $this->artisanOutput());

            $this->report($progress, 'cleanup', 'Final optimization cleanup', 'running', 94, 'Clearing bootstrap caches so the installed environment starts cleanly.');
            Artisan::call('optimize:clear');
            $this->report($progress, 'cleanup', 'Final optimization cleanup', 'completed', 97, 'Final cache cleanup completed.', $this->artisanOutput());

            // Local public media storage must be linked before the final deep
            // storage data-plane attestation. The installer never overwrites a
            // pre-existing wrong link: Laravel's storage:link command is only
            // invoked when the expected public/storage path is absent.
            $this->prepareLocalMediaPublicLink();

            // The permanent installed lock is deliberately the final mutation. If
            // any earlier provisioning/cleanup stage fails the wizard remains
            // recoverable instead of locking a partially completed installation.
            $this->report($progress, 'lock', 'Installation lock', 'running', 98, 'Writing the permanent installation state.');
            $runtimeMetadata = $this->buildRuntimeInstallationMetadata(
                $key,
                $dependencyTrust,
                $runtimeReadiness,
            );

            $this->state->markInstalled([
                'installation_id' => (string) Str::uuid(),
                'app_name' => (string) $input['app_name'],
                'app_url' => rtrim((string) $input['app_url'], '/'),
                'database_driver' => $db['driver'],
                'database' => $db['database'],
                'database_had_existing_objects' => $tableCount > 0,
                'database_install_action' => $databaseInstallAction,
                'database_protection_mode' => $databaseProtectionMode,
                'admin_password_strength' => (string) ($input['_password_strength'] ?? 'unknown'),
                'admin_password_risk_consent' => (bool) ($input['_password_strength_consent'] ?? false),
                'environment_mode' => $environmentMode,
                'admin_user_id' => $admin->id,
                'runtime' => $sync,
                'version' => (string) config('nexora.version', 'unknown'),
                // Volatile environment/activation/service/process fingerprints
                // are finalized only after installed.lock changes deployment mode.
                'post_install_identity_finalized' => false,
                ...$runtimeMetadata,
            ]);

            // installed.lock is the durable commit point. It changes install-sensitive Laravel configuration
            // (session/cache namespaces and installed deployment mode). Do not
            // certify those volatile fingerprints inside this long-lived installer
            // request. The controller deliberately performs handoff finalization
            // in the next fresh HTTP request, which loads the committed .env and
            // installed.lock exactly like login/admin will.
            $this->report(
                $progress,
                'handoff',
                'Runtime handoff',
                'completed',
                100,
                'Installation committed. Final runtime identity will be sealed by the next fresh handoff request before login.',
            );

            // Everything below is best-effort housekeeping and must never turn
            // a committed + verified installation into a reported failure.
            $this->finalizeCommittedInstallation($input);
        } finally {
            flock($mutex, LOCK_UN);
            fclose($mutex);
            @unlink($mutexPath);
            $this->activeRunId = null;
        }
    }

    /** @return array<string, mixed> */
    private function buildRuntimeInstallationMetadata(
        string $applicationKey,
        array $dependencyTrust,
        array $preflightReadiness,
    ): array
    {
        $framework = $this->frameworkCompatibility->assertCompatible();

        // Re-resolve dependency/runtime trust at the commit boundary. A long
        // installer request must never seal preflight dependency provenance if
        // lock files or installed Composer runtime packages changed meanwhile.
        $finalDependencyTrust = $this->installDependencyTrust->resolve();
        $this->assertDependencySnapshotStable($dependencyTrust, $finalDependencyTrust);

        $dependencies = (array) ($finalDependencyTrust['dependency_state'] ?? []);
        $readiness = $this->installationReadiness->assertReady($finalDependencyTrust);
        $this->assertCommitSnapshotStable($preflightReadiness, $readiness);
        $deploymentVerification = (array) ($readiness['details']['deployment'] ?? []);
        $deployment = (array) ($deploymentVerification['current'] ?? []);
        $sourceTree = strtolower(trim((string) ($deploymentVerification['source_tree_sha256'] ?? '')));
        if (($deploymentVerification['ok'] ?? false) !== true
            || preg_match('/^[a-f0-9]{64}$/', $sourceTree) !== 1) {
            throw new RuntimeException(
                'Final installation metadata cannot be committed because the full Nexora source tree is not sealed to a valid deployment identity.',
            );
        }

        $host = (array) ($readiness['details']['host_clock'] ?? []);
        $resources = (array) ($readiness['details']['resources'] ?? []);
        $policy = (array) ($readiness['details']['policy'] ?? []);
        $processes = (array) ($readiness['details']['processes'] ?? []);
        $activationReadiness = (array) ($readiness['details']['activation'] ?? []);
        $activation = $this->activation->bootstrap('initial-install');
        $engine = $this->runtimeEngine->current();
        $database = $this->databaseDataPlane->current(true);
        $storage = $this->storageDataPlane->current(true);
        $services = $this->serviceDataPlane->current(true);

        $bootstrapReceipt = $finalDependencyTrust['bootstrap_receipt'] ?? null;
        if (is_array($bootstrapReceipt)) {
            $this->installDependencyTrust->commitBootstrapReceipt($bootstrapReceipt);
        }

        $dependencyHashes = (array) ($dependencies['hashes'] ?? []);

        return [
            'deployment_generation' => $deployment['generation'] ?? null,
            'deployment_source_attestation_status' => 'pass',
            'release_source_tree_sha256' => $sourceTree,
            'installation_commit_snapshot_status' => 'stable',
            'installation_preflight_source_tree_sha256' => $preflightReadiness['details']['deployment']['source_tree_sha256'] ?? null,
            'installation_preflight_dependency_fingerprint' => $dependencyTrust['dependency_state']['fingerprint'] ?? null,
            'frontend_manifest_sha256' => $deployment['frontend_manifest_sha256'] ?? null,
            'composer_lock_sha256' => $dependencyHashes['composer_lock_sha256'] ?? null,
            'package_lock_sha256' => $dependencyHashes['package_lock_sha256'] ?? null,
            'runtime_dependency_fingerprint' => $dependencies['fingerprint'] ?? null,
            'dependency_trust_mode' => $finalDependencyTrust['trust_mode'] ?? 'unknown',
            'dependency_review_required' => (bool) ($finalDependencyTrust['review_required'] ?? true),
            'dependency_review_status' => $dependencies['review_status'] ?? 'missing',
            'dependency_bootstrap_receipt_sha256' => $finalDependencyTrust['bootstrap_receipt']['receipt_sha256'] ?? null,
            'reviewed_locks_sha256' => $this->hashFile(base_path(\App\Nexora\Foundation\Runtime\ReviewedDependencyState::REVIEW_PATH)),
            'laravel_framework_version' => $framework['installed_version'] ?? null,
            'cache_namespace' => $this->deployment->cacheNamespace(),
            'session_schema' => (int) config('nexora-runtime.deployment.session_schema', 1),
            'key_fingerprint' => $this->runtimeEnvironment->activeKeyFingerprint()
                ?? hash('sha256', $applicationKey),
            'runtime_environment_fingerprint' => $this->runtimeEnvironment->fingerprintValue(),
            'activation_epoch' => $activation['activation_epoch'] ?? null,
            'runtime_activation_fingerprint' => $activation['activation_fingerprint'] ?? null,
            'runtime_activation_cache_sha256' => $activation['framework_cache']['snapshot_sha256'] ?? null,
            'runtime_activated_at' => now()->toIso8601String(),
            'runtime_engine_fingerprint' => $engine['fingerprint'] ?? null,
            'php_version' => $engine['materials']['php_version'] ?? PHP_VERSION,
            'extension_profile_sha256' => $engine['materials']['extension_profile_sha256'] ?? null,
            'pdo_drivers_sha256' => $engine['materials']['pdo_drivers_sha256'] ?? null,
            'database_data_plane_fingerprint' => $database['fingerprint'] ?? null,
            'database_schema_fingerprint' => $database['schema_fingerprint'] ?? null,
            'database_server_version' => $database['normalized_server_version'] ?? null,
            'database_session_profile_sha256' => hash(
                'sha256',
                json_encode($database['session_profile'] ?? [], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ),
            'runtime_host_fingerprint' => $host['fingerprint'] ?? null,
            'runtime_process_fingerprint' => $processes['fingerprint'] ?? null,
            'runtime_policy_fingerprint' => $policy['fingerprint'] ?? null,
            'runtime_policy_deep_sha256' => $policy['deep']['deep_sha256'] ?? null,
            'runtime_resource_fingerprint' => $resources['fingerprint'] ?? null,
            'resource_deep_probe_sha256' => $resources['deep']['deep_sha256'] ?? null,
            'resource_memory_limit_bytes' => $resources['snapshot']['memory_limit_bytes'] ?? null,
            'resource_installation_status' => $resources['installation_status'] ?? 'fail',
            'resource_strict_certification_status' => $resources['status'] ?? 'fail',
            'resource_installation_warnings' => array_values((array) ($resources['installation_warnings'] ?? [])),
            'policy_installation_status' => $policy['installation_status'] ?? 'fail',
            'policy_strict_certification_status' => $policy['status'] ?? 'fail',
            'policy_installation_warnings' => array_values((array) ($policy['installation_warnings'] ?? [])),
            'process_installation_status' => $processes['installation_status'] ?? 'fail',
            'process_strict_certification_status' => $processes['status'] ?? 'fail',
            'process_installation_warnings' => array_values((array) ($processes['installation_warnings'] ?? [])),
            'activation_installation_status' => $activationReadiness['installation_status'] ?? 'fail',
            'activation_installation_warnings' => array_values((array) ($activationReadiness['installation_warnings'] ?? [])),
            'host_clock_deep_probe_sha256' => $host['deep']['deep_sha256'] ?? null,
            'host_clock_skew_ms' => $host['deep']['details']['clock']['skew_ms'] ?? null,
            'host_clock_installation_status' => $host['installation_status'] ?? 'fail',
            'host_clock_strict_certification_status' => $host['strict_status'] ?? 'fail',
            'host_clock_installation_max_skew_ms' => $host['installation_max_database_clock_skew_ms'] ?? null,
            'host_clock_installation_warnings' => array_values((array) ($host['installation_warnings'] ?? [])),
            'host_os_family' => $host['materials']['os_family'] ?? PHP_OS_FAMILY,
            'host_machine_arch' => $host['materials']['machine_arch'] ?? null,
            'runtime_storage_fingerprint' => $storage['fingerprint'] ?? null,
            'runtime_service_fingerprint' => $services['fingerprint'] ?? null,
            'service_deep_probe_sha256' => $services['deep']['deep_sha256'] ?? null,
            'cache_service_store' => $services['materials']['cache']['store'] ?? null,
            'queue_service_connection' => $services['materials']['queue']['connection'] ?? null,
            'mail_service_default' => $services['materials']['mail']['default'] ?? null,
            'object_storage_disk' => $storage['roles']['object']['disk'] ?? null,
            'media_storage_disk' => $storage['roles']['media']['disk'] ?? null,
            'backup_storage_disk' => $storage['roles']['backup']['disk'] ?? null,
            'storage_deep_probe_sha256' => $storage['deep']['deep_sha256'] ?? null,
        ];
    }

    private function assertInstallerIsOpen(): void
    {
        if (! $this->state->isInstalled()) {
            return;
        }

        $inspection = $this->state->inspect();
        if (($inspection['valid'] ?? false) === true) {
            throw new RuntimeException('Nexora is already installed.');
        }

        throw new RuntimeException(
            'Nexora installation lock exists but failed integrity validation. '
            .'The installer remains closed to protect the existing database. '
            .'Run `php artisan nexora:install:lock-status` for details.',
        );
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after */
    private function assertDependencySnapshotStable(array $before, array $after): void
    {
        $beforeState = (array) ($before['dependency_state'] ?? []);
        $afterState = (array) ($after['dependency_state'] ?? []);
        $beforeFramework = (array) ($before['framework'] ?? []);
        $afterFramework = (array) ($after['framework'] ?? []);

        $checks = [
            'trust_mode' => [$before['trust_mode'] ?? null, $after['trust_mode'] ?? null],
            'review_required' => [$before['review_required'] ?? null, $after['review_required'] ?? null],
            'runtime_dependency_fingerprint' => [$beforeState['fingerprint'] ?? null, $afterState['fingerprint'] ?? null],
            'composer_manifest_sha256' => [$beforeState['hashes']['composer_manifest_sha256'] ?? null, $afterState['hashes']['composer_manifest_sha256'] ?? null],
            'package_manifest_sha256' => [$beforeState['hashes']['package_manifest_sha256'] ?? null, $afterState['hashes']['package_manifest_sha256'] ?? null],
            'composer_lock_sha256' => [$beforeState['hashes']['composer_lock_sha256'] ?? null, $afterState['hashes']['composer_lock_sha256'] ?? null],
            'package_lock_sha256' => [$beforeState['hashes']['package_lock_sha256'] ?? null, $afterState['hashes']['package_lock_sha256'] ?? null],
            'laravel_framework_locked_version' => [$beforeState['laravel_framework_locked_version'] ?? null, $afterState['laravel_framework_locked_version'] ?? null],
            'laravel_framework_running_version' => [$beforeFramework['installed_version'] ?? null, $afterFramework['installed_version'] ?? null],
        ];

        $changed = [];
        foreach ($checks as $name => [$old, $new]) {
            if ($old !== $new) {
                $changed[] = $name;
            }
        }

        if ($changed !== []) {
            throw new RuntimeException(
                'Installation dependency provenance changed after preflight ['.implode(', ', $changed).']. '
                .'No permanent installation lock was written. Restart the installer against one stable dependency generation.',
            );
        }
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after */
    private function assertCommitSnapshotStable(array $before, array $after): void
    {
        $beforeSource = (array) ($before['details']['source'] ?? []);
        $afterSource = (array) ($after['details']['source'] ?? []);
        $beforeDeployment = (array) ($before['details']['deployment'] ?? []);
        $afterDeployment = (array) ($after['details']['deployment'] ?? []);

        $checks = [
            'source_activation_fingerprint' => [$beforeSource['fingerprint'] ?? null, $afterSource['fingerprint'] ?? null],
            'critical_source_set_fingerprint' => [$beforeSource['source_set_fingerprint'] ?? null, $afterSource['source_set_fingerprint'] ?? null],
            'runtime_class_fingerprint' => [$beforeSource['runtime_class_fingerprint'] ?? null, $afterSource['runtime_class_fingerprint'] ?? null],
            'source_tree_sha256' => [$beforeDeployment['source_tree_sha256'] ?? null, $afterDeployment['source_tree_sha256'] ?? null],
            'deployment_generation' => [$beforeDeployment['current']['generation'] ?? null, $afterDeployment['current']['generation'] ?? null],
        ];

        $changed = [];
        foreach ($checks as $name => [$old, $new]) {
            if ($old !== $new) {
                $changed[] = $name;
            }
        }

        if ($changed !== []) {
            throw new RuntimeException(
                'Installation source/runtime provenance changed after preflight ['.implode(', ', $changed).']. '
                .'No permanent installation lock was written. Reload/restart PHP and retry from a stable exact source generation.',
            );
        }
    }

    /** @param array<string,mixed> $input */
    private function finalizeCommittedInstallation(array $input): void
    {
        try {
            if (! empty($input['db_backup_token'])) {
                $this->backups->remove((string) $input['db_backup_token']);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $deploymentAccessKey = base_path('storage/app/nexora/deployment-access.key');
            if (is_file($deploymentAccessKey)) {
                @unlink($deploymentAccessKey);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

    }

    private function hashFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /** @param array<string,mixed> $state */
    private function assertInstallationHostClockStatus(array $state, string $stage): void
    {
        if (($state['installation_status'] ?? $state['status'] ?? 'fail') === 'pass') {
            return;
        }

        $reasons = array_values(array_filter(array_map(
            static fn (mixed $reason): string => trim((string) $reason),
            (array) ($state['installation_blocking_reasons'] ?? []),
        )));
        $detail = $reasons === []
            ? 'Unknown host/clock installation safety failure.'
            : implode(' ', $reasons);

        throw new RuntimeException(
            ucfirst($stage).' host/clock safety check failed. '.$detail
            .' Run `php artisan nexora:runtime:host-status --installation` for exact diagnostics.',
        );
    }

    /** @param array<string,mixed> $state */
    private function hostClockProgressMessage(array $state): string
    {
        $clock = (array) ($state['deep']['details']['clock'] ?? []);
        $skew = is_numeric($clock['skew_ms'] ?? null)
            ? (int) $clock['skew_ms']
            : null;
        $warnings = array_values((array) ($state['installation_warnings'] ?? []));
        $message = sprintf(
            'Installation-safe host/clock checks passed%s. Strict target certification: %s.',
            $skew !== null ? sprintf(' (DB clock skew %d ms)', $skew) : '',
            (string) ($state['strict_status'] ?? 'unknown'),
        );

        if ($warnings !== []) {
            $message .= ' Pending strict notes: '.implode(' ', array_slice($warnings, 0, 3));
        }

        return $message;
    }


    /** @param callable(array<string,mixed>):void|null $progress */
    private function report(?callable $progress, string $stage, string $label, string $status, int $percent, string $message, ?string $output = null): void
    {
        if ($progress === null) {
            return;
        }

        $cancellable = in_array($stage, ['preflight', 'database', 'host-clock', 'runtime-readiness', 'backup'], true);
        if ($this->activeRunId !== null) {
            $this->runControl->update($this->activeRunId, $stage, $cancellable);
        }
        $payload = [
            'type' => 'step',
            'stage' => $stage,
            'cancellable' => $cancellable,
            'label' => $label,
            'status' => $status,
            'progress' => max(0, min(100, $percent)),
            'message' => $message,
        ];
        if ($output !== null && $output !== '') {
            $payload['output'] = $output;
        }
        $progress($payload);
    }

    private function artisanOutput(): ?string
    {
        $output = trim((string) Artisan::output());
        if ($output === '') {
            return null;
        }
        if (strlen($output) > 12000) {
            $output = '[Earlier Artisan output truncated]'.PHP_EOL.substr($output, -10000);
        }
        return $output;
    }

    private function prepareLocalMediaPublicLink(): void
    {
        if (! (bool) config('nexora-storage-runtime.require_public_link_if_local', true)) {
            return;
        }

        $disk = (string) config('nexora-storage-runtime.media_disk', 'public');
        $diskConfig = (array) config("filesystems.disks.{$disk}", []);
        if (strtolower((string) ($diskConfig['driver'] ?? '')) !== 'local') {
            return;
        }

        $expectedRoot = realpath(storage_path('app/public'));
        $configuredRoot = realpath((string) ($diskConfig['root'] ?? ''));
        if (! is_string($expectedRoot) || ! is_string($configuredRoot) || $this->normalizeInstallerPath($expectedRoot) !== $this->normalizeInstallerPath($configuredRoot)) {
            return;
        }

        $link = public_path('storage');
        if (is_link($link) || is_dir($link)) {
            $resolved = realpath($link);
            if (! is_string($resolved) || $this->normalizeInstallerPath($resolved) !== $this->normalizeInstallerPath($expectedRoot)) {
                throw new RuntimeException('public/storage already exists but does not resolve to storage/app/public. Nexora refuses to overwrite it automatically.');
            }
            return;
        }

        if (Artisan::call('storage:link') !== 0) {
            throw new RuntimeException('Unable to create the required public/storage link. Create it manually and retry installation.');
        }
        $resolved = realpath($link);
        if (! is_string($resolved) || $this->normalizeInstallerPath($resolved) !== $this->normalizeInstallerPath($expectedRoot)) {
            throw new RuntimeException('storage:link completed but public/storage does not resolve to storage/app/public.');
        }
    }

    private function normalizeInstallerPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = rtrim($path, '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }

    /** @param array{driver:string,host?:string,port?:int,database:string,username?:string,password?:string} $db */
    private function applyDatabaseRuntimeConfig(array $db): void
    {
        $connectionName = (string) $db['driver'];
        Config::set('database.default', $connectionName);
        Config::set('database.connections.'.$connectionName, $this->database->laravelConnection($db));
        DB::purge($connectionName);
        DB::reconnect($connectionName);
    }

    private function checkpoint(string $stage, bool $cancellable): void
    {
        if ($this->activeRunId === null || $this->activeRunId === '') {
            return;
        }
        // When transitioning into a protected non-cancellable stage, honor any
        // cancellation that arrived while the previous safe stage was active.
        if (! $cancellable) {
            $this->runControl->throwIfCancelled($this->activeRunId);
        }
        $this->runControl->update($this->activeRunId, $stage, $cancellable);
        if ($cancellable) {
            $this->runControl->throwIfCancelled($this->activeRunId);
        }
    }

}
