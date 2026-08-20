<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeManager;
use Illuminate\Console\Command;

final class UpgradePlanCommand extends Command
{
    protected $signature='nexora:upgrade:plan {--backup= : Verified nx_runtime_backup_runs identifier} {--external-backup-evidence= : Relative or absolute JSON evidence for an externally managed backup}';
    protected $description='Create a fail-closed, expiring Nexora in-place upgrade plan after signed-release admission, compatibility and backup checks.';
    public function handle(UpgradeManager $upgrades): int
    {
        $plan=$upgrades->plan($this->option('backup') ?: null,$this->option('external-backup-evidence') ?: null);
        $this->line(json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return ($plan['status']??null)==='ready' ? self::SUCCESS : self::FAILURE;
    }
}
