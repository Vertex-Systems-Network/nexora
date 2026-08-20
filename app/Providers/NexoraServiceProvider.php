<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\Nexora\CapabilityListCommand;
use App\Console\Commands\Nexora\MakeExtensionCommand;
use App\Console\Commands\Nexora\ExtensionListCommand;
use App\Console\Commands\Nexora\InstallerDoctorCommand;
use App\Console\Commands\Nexora\InstallationLockStatusCommand;
use App\Console\Commands\Nexora\PublishingRunCommand;
use App\Console\Commands\Nexora\ModuleListCommand;
use App\Console\Commands\Nexora\RuntimeCacheCommand;
use App\Console\Commands\Nexora\RuntimeClearCommand;
use App\Console\Commands\Nexora\RuntimeSyncCommand;
use App\Console\Commands\Nexora\SentinelScanCommand;
use App\Console\Commands\Nexora\SupplyChainVerifyCommand;
use App\Console\Commands\Nexora\SourceStatusCommand;
use App\Console\Commands\Nexora\SourceActivateCommand;
use App\Console\Commands\Nexora\ThemeListCommand;
use App\Console\Commands\Nexora\ThemeActivateCommand;
use App\Console\Commands\Nexora\ThemeRollbackCommand;
use App\Console\Commands\Nexora\ThemeMakeCommand;
use App\Console\Commands\Nexora\TransferDoctorCommand;
use App\Console\Commands\Nexora\UpgradePlanCommand;
use App\Console\Commands\Nexora\UpgradeApplyCommand;
use App\Console\Commands\Nexora\UpgradePreflightCommand;
use App\Console\Commands\Nexora\UpgradeStatusCommand;
use App\Console\Commands\Nexora\UpgradeRecoveryStatusCommand;
use App\Console\Commands\Nexora\UpgradeLineageExportCommand;
use App\Console\Commands\Nexora\UpgradeRecoveryRecordCommand;
use App\Console\Commands\Nexora\UpgradeMaintenanceLeaseCommand;
use App\Console\Commands\Nexora\UpgradeClusterStatusCommand;
use App\Console\Commands\Nexora\UpgradeNodeStatusCommand;
use App\Console\Commands\Nexora\UpgradeClusterLockCommand;
use App\Console\Commands\Nexora\UpgradeSchedulerLeaseCommand;
use App\Console\Commands\Nexora\UpgradeQuiescenceCommand;
use App\Console\Commands\Nexora\UpgradeCutoverStatusCommand;
use App\Console\Commands\Nexora\RuntimeDeploymentStatusCommand;
use App\Console\Commands\Nexora\RuntimeActivationStatusCommand;
use App\Console\Commands\Nexora\RuntimeActivationRotateCommand;
use App\Console\Commands\Nexora\RuntimeEngineStatusCommand;
use App\Console\Commands\Nexora\RuntimeEnvironmentStatusCommand;
use App\Console\Commands\Nexora\RuntimeKeyRotationCommand;
use App\Console\Commands\Nexora\DatabaseDataPlaneStatusCommand;
use App\Console\Commands\Nexora\RuntimeStorageStatusCommand;
use App\Console\Commands\Nexora\RuntimeServiceStatusCommand;
use App\Console\Commands\Nexora\RuntimeHostStatusCommand;
use App\Console\Commands\Nexora\RuntimeInstallReadinessCommand;
use App\Console\Commands\Nexora\RuntimePostInstallStatusCommand;
use App\Console\Commands\Nexora\RuntimePostInstallReconcileCommand;
use App\Console\Commands\Nexora\RuntimeResourceStatusCommand;
use App\Console\Commands\Nexora\RuntimePolicyStatusCommand;
use App\Console\Commands\Nexora\RuntimeProcessHeartbeatCommand;
use App\Console\Commands\Nexora\RuntimeProcessStatusCommand;
use App\Console\Commands\Nexora\RuntimeDependencyReconcileCommand;
use App\Console\Commands\Nexora\RuntimeDependencyReviewSyncCommand;
use App\Console\Commands\Nexora\RuntimeDependencyStatusCommand;
use App\Console\Commands\Nexora\RuntimeCompatibilityStatusCommand;
use App\Nexora\Foundation\Capabilities\CapabilityGuard;
use App\Nexora\Foundation\Capabilities\CapabilityRegistry;
use App\Nexora\Foundation\Contracts\CapabilityGuardContract;
use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use App\Nexora\Foundation\Contracts\NexoraKernelContract;
use App\Nexora\Foundation\Contracts\RuntimeContextContract;
use App\Nexora\Foundation\Modules\ModuleRegistry;
use App\Nexora\Foundation\Runtime\CapabilityDefinition;
use App\Nexora\Foundation\Runtime\CapabilityRisk;
use App\Nexora\Foundation\Runtime\NexoraKernel;
use App\Nexora\Foundation\Runtime\RuntimeContext;
use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use Illuminate\Support\ServiceProvider;

final class NexoraServiceProvider extends ServiceProvider
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    public function register(): void
    {
        $this->app->singleton(ModuleRegistryContract::class, ModuleRegistry::class);
        $this->app->singleton(CapabilityRegistryContract::class, CapabilityRegistry::class);
        $this->app->singleton(RuntimeContextContract::class, RuntimeContext::class);
        $this->app->singleton(CapabilityGuardContract::class, CapabilityGuard::class);
        $this->app->singleton(NexoraKernelContract::class, NexoraKernel::class);
        $this->app->singleton(VersionConstraintMatcher::class);
        $this->app->singleton(RuntimeSynchronizer::class);
    }

    public function boot(
        CapabilityRegistryContract $capabilities,
        NexoraKernelContract $kernel,
    ): void {
        foreach ((array) config('nexora.capabilities', []) as $definition) {
            $capabilities->register(new CapabilityDefinition(
                slug: (string) $definition['slug'],
                name: (string) $definition['name'],
                group: (string) $definition['group'],
                risk: CapabilityRisk::from((string) ($definition['risk'] ?? 'normal')),
                description: (string) ($definition['description'] ?? ''),
            ));
        }

        $kernel->boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleListCommand::class,
                CapabilityListCommand::class,
                RuntimeSyncCommand::class,
                RuntimeCacheCommand::class,
                RuntimeClearCommand::class,
                SentinelScanCommand::class,
                SupplyChainVerifyCommand::class,
                SourceStatusCommand::class,
                SourceActivateCommand::class,
                ThemeListCommand::class,
                ThemeActivateCommand::class,
                ThemeRollbackCommand::class,
                ThemeMakeCommand::class,
                InstallerDoctorCommand::class,
                InstallationLockStatusCommand::class,
                PublishingRunCommand::class,
                ExtensionListCommand::class,
                MakeExtensionCommand::class,
                TransferDoctorCommand::class,
                UpgradePreflightCommand::class,
                UpgradePlanCommand::class,
                UpgradeApplyCommand::class,
                UpgradeStatusCommand::class,
                UpgradeRecoveryStatusCommand::class,
                UpgradeLineageExportCommand::class,
                UpgradeRecoveryRecordCommand::class,
                UpgradeMaintenanceLeaseCommand::class,
                UpgradeClusterStatusCommand::class,
                UpgradeNodeStatusCommand::class,
                UpgradeClusterLockCommand::class,
                UpgradeSchedulerLeaseCommand::class,
                UpgradeQuiescenceCommand::class,
                UpgradeCutoverStatusCommand::class,
                RuntimeDeploymentStatusCommand::class,
                RuntimeActivationStatusCommand::class,
                RuntimeActivationRotateCommand::class,
                RuntimeEngineStatusCommand::class,
                RuntimeEnvironmentStatusCommand::class,
                RuntimeKeyRotationCommand::class,
                DatabaseDataPlaneStatusCommand::class,
                RuntimeStorageStatusCommand::class,
                RuntimeServiceStatusCommand::class,
                RuntimeHostStatusCommand::class,
                RuntimeInstallReadinessCommand::class,
                RuntimePostInstallStatusCommand::class,
                RuntimePostInstallReconcileCommand::class,
                RuntimeResourceStatusCommand::class,
                RuntimePolicyStatusCommand::class,
                RuntimeProcessHeartbeatCommand::class,
                RuntimeProcessStatusCommand::class,
                RuntimeDependencyReconcileCommand::class,
                RuntimeDependencyReviewSyncCommand::class,
                RuntimeDependencyStatusCommand::class,
                RuntimeCompatibilityStatusCommand::class,
            ]);
        }
    }
}
