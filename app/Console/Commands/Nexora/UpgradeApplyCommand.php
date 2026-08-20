<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeManager;
use Illuminate\Console\Command;

final class UpgradeApplyCommand extends Command
{
    protected $signature='nexora:upgrade:apply {--yes : Confirm maintenance mode and protected database migration execution}';
    protected $description='Apply a previously verified Nexora platform upgrade plan. Failures remain in maintenance mode for backup-based recovery.';
    public function handle(UpgradeManager $upgrades): int
    {
        if(! $this->option('yes')) { $this->error('Upgrade apply requires --yes after reviewing the generated plan and verified backup.'); return self::FAILURE; }
        try { $result=$upgrades->apply(); $this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        catch(\Throwable $e){ $this->error($e->getMessage()); $this->warn('Nexora remains in maintenance mode after a protected-stage failure. Restore the verified backup before serving traffic.'); return self::FAILURE; }
    }
}
