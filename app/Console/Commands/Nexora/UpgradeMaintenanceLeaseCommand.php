<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeMaintenanceLease;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use Illuminate\Console\Command;

final class UpgradeMaintenanceLeaseCommand extends Command
{
    protected $signature='nexora:upgrade:maintenance-lease {--release : Release a stale lease only when traffic is already live and no protected transaction exists} {--confirm= : Must equal RELEASE}';
    protected $description='Inspect or safely release stale Nexora upgrade maintenance ownership without changing maintenance mode.';
    public function handle(UpgradeMaintenanceLease $lease,UpgradeTransactionJournal $journal): int
    {
        try{$current=$lease->read();$tx=$journal->read();}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        if(!$this->option('release')){$this->line(json_encode(['maintenance_mode_active'=>app()->isDownForMaintenance(),'lease'=>$current,'transaction'=>$tx],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return self::SUCCESS;}
        if((string)$this->option('confirm')!=='RELEASE'){$this->error('Stale maintenance-lease release requires --confirm=RELEASE.');return self::FAILURE;}
        if(app()->isDownForMaintenance()){$this->error('Refusing to release maintenance ownership while the application is still in maintenance mode.');return self::FAILURE;}
        if(is_array($tx)&&in_array((string)($tx['status']??''),['running','recovery_required'],true)){$this->error('Refusing to release maintenance ownership while a protected upgrade transaction is active.');return self::FAILURE;}
        if(!is_array($current)){$this->info('No maintenance lease exists.');return self::SUCCESS;}
        try{$lease->release((string)($current['upgrade_id']??''));}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        $this->info('Stale maintenance lease released. Maintenance mode was not changed.');return self::SUCCESS;
    }
}
