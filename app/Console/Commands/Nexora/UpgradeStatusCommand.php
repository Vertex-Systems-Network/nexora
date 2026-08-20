<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeCompatibilityService;
use App\Nexora\Foundation\Upgrade\UpgradeMaintenanceLease;
use App\Nexora\Foundation\Upgrade\UpgradePlanStore;
use App\Nexora\Foundation\Upgrade\UpgradeRecoveryDecisionStore;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class UpgradeStatusCommand extends Command
{
    protected $signature='nexora:upgrade:status';
    protected $description='Show installed/current Nexora versions, active upgrade plan, maintenance ownership, recovery decision and compatibility status.';
    public function handle(InstallationState $installation, UpgradePlanStore $plans, UpgradeCompatibilityService $compatibility, UpgradeTransactionJournal $journal, UpgradeMaintenanceLease $lease, UpgradeRecoveryDecisionStore $decisions): int
    {
        $payload=[
            'installed'=>$installation->isInstalled(),
            'installed_metadata'=>$installation->metadata(),
            'target_version'=>(string)config('nexora.version'),
            'active_plan'=>$plans->read(),
            'transaction_journal'=>$journal->read(),
            'maintenance_mode_active'=>app()->isDownForMaintenance(),
            'maintenance_lease'=>$lease->read(),
            'recovery_decision'=>$decisions->read(),
            'compatibility'=>$compatibility->assess(),
        ];
        $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return self::SUCCESS;
    }
}
