<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use Illuminate\Console\Command;

final class UpgradeClusterLockCommand extends Command
{
    protected $signature='nexora:upgrade:cluster-lock {--release : Release only an expired lease or an explicitly matched recovery-held lease} {--upgrade-id= : Required when releasing recovery_required lease} {--confirm= : Must equal RELEASE}';
    protected $description='Inspect or explicitly release a stale/recovery distributed platform-upgrade lease without changing database/schema state.';
    public function handle(UpgradeClusterCoordinator $cluster,UpgradeTransactionJournal $journal): int
    {
        $lease=$cluster->leaseStatus();if(!$this->option('release')){$this->line(json_encode(['lease'=>$lease,'automatic_release'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
        if((string)$this->option('confirm')!=='RELEASE'){$this->error('Release refused. Pass --confirm=RELEASE after recovery is complete.');return self::INVALID;}
        if(!is_array($lease)){$this->info('No distributed upgrade lease exists.');return self::SUCCESS;}
        $tx=$journal->read();if(is_array($tx)&&in_array((string)($tx['status']??''),['running','recovery_required'],true)){$this->error('Release refused while the local upgrade transaction is running or recovery_required.');return self::FAILURE;}
        if(app()->isDownForMaintenance()){$this->error('Release refused while application maintenance mode is active.');return self::FAILURE;}
        $meta=is_array($lease['metadata']??null)?$lease['metadata']:[];$recovery=($meta['recovery_required']??false)===true;$expires=strtotime((string)($lease['expires_at']??''));$expired=$expires!==false&&$expires<=time();
        if(!$expired&&!$recovery){$this->error('Release refused: distributed upgrade lease is still live and is not marked recovery_required.');return self::FAILURE;}
        if($recovery){$provided=trim((string)$this->option('upgrade-id'));$expected=trim((string)($meta['upgrade_id']??''));if($provided===''||$expected===''||!hash_equals($expected,$provided)){$this->error('Recovery-held release requires --upgrade-id matching the lease metadata.');return self::FAILURE;}}
        $cluster->forceReleaseIfSafe($recovery?trim((string)$this->option('upgrade-id')):null);$this->info('Distributed upgrade lease released after operator validation. No database rollback or traffic-mode mutation was performed.');return self::SUCCESS;
    }
}
