<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeRecoveryDecisionStore;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use Illuminate\Console\Command;

final class UpgradeRecoveryRecordCommand extends Command
{
    protected $signature='nexora:upgrade:recovery-record {decision : restore_verified_backup|retry_pre_migration|manual_investigation} {--operator= : Real operator identity} {--note= : Non-secret recovery note} {--confirm= : Must equal RECORD}';
    protected $description='Record an integrity-bound operator recovery decision without performing rollback, restore or traffic changes.';
    public function handle(UpgradeTransactionJournal $journal,UpgradeRecoveryDecisionStore $decisions): int
    {
        if((string)$this->option('confirm')!=='RECORD'){$this->error('Recovery decision recording requires --confirm=RECORD.');return self::FAILURE;}
        try{$tx=$journal->read();if(!is_array($tx)||!in_array((string)($tx['status']??''),['running','recovery_required'],true))throw new \RuntimeException('No interrupted/recovery-required upgrade transaction is available for a recovery decision.');$record=$decisions->record($tx,(string)$this->argument('decision'),(string)$this->option('operator'),(string)$this->option('note'));}
        catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        $this->line(json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return self::SUCCESS;
    }
}
