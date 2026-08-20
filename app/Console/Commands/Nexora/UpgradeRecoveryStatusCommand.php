<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeMaintenanceLease;
use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use App\Nexora\Foundation\Upgrade\UpgradePlanStore;
use App\Nexora\Foundation\Upgrade\UpgradeRecoveryDecisionStore;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use App\Nexora\Foundation\Upgrade\TrustedUpdateAdmission;
use Illuminate\Console\Command;

final class UpgradeRecoveryStatusCommand extends Command
{
    protected $signature='nexora:upgrade:recovery-status';
    protected $description='Show crash-safe upgrade journal, maintenance ownership, recovery decision, active plan and trusted-update state without mutating recovery data.';
    public function handle(UpgradeTransactionJournal $journal,UpgradePlanStore $plans,TrustedUpdateAdmission $admission,UpgradeMaintenanceLease $lease,UpgradeRecoveryDecisionStore $decisions,UpgradeClusterCoordinator $cluster): int
    {
        try{$tx=$journal->read();$plan=$plans->read();$trusted=$admission->verify();$maintenanceLease=$lease->read();$decision=$decisions->read();}
        catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
        $stage=is_array($tx)?(string)($tx['stage']??''):'';$status=is_array($tx)?(string)($tx['status']??''):'';$updated=is_array($tx)?strtotime((string)($tx['updated_at']??'')):false;$staleMinutes=(int)config('nexora-upgrade.transaction_stale_minutes',15);$staleRunning=$status==='running'&&$updated!==false&&$updated<=time()-$staleMinutes*60;
        $mutationStages=['migrations_started','migrations_completed','migration_ledger_converged','runtime_sync_completed','runtime_cache_completed','post_upgrade_health_passed','installation_metadata_committing','installation_metadata_committed','post_metadata_health_passed','maintenance_disabled','completed'];$dataMutationPossible=in_array($stage,$mutationStages,true);
        $needsRecovery=$status==='recovery_required'||$staleRunning;
        $leaseMatches=is_array($tx)&&is_array($maintenanceLease)&&($tx['upgrade_id']??null)===($maintenanceLease['upgrade_id']??null)&&($tx['maintenance_lease_sha256']??null)===($maintenanceLease['lease_sha256']??null);
        $maintenanceActual=app()->isDownForMaintenance();
        $action=$needsRecovery?($dataMutationPossible?'Keep maintenance mode enabled. Restore the exact verified source-version backup and source release before serving traffic; blind down-migrations are forbidden. Record the operator decision with nexora:upgrade:recovery-record.':'An interrupted pre-migration upgrade is suspected. Keep traffic disabled, verify source/database state against the sealed plan and backup, then record retry_pre_migration or manual_investigation; no automatic rollback is performed.'):'No interrupted/recovery-required upgrade transaction is currently recorded.';
        $payload=['transaction'=>$tx,'distributed_upgrade_lease'=>$cluster->leaseStatus(),'active_plan'=>$plan,'trusted_update'=>['ok'=>$trusted['ok'],'errors'=>$trusted['errors'],'receipt_sha256'=>$trusted['receipt_sha256']],'maintenance_mode_active'=>$maintenanceActual,'maintenance_lease'=>$maintenanceLease,'maintenance_lease_matches_transaction'=>$leaseMatches,'recovery_decision'=>$decision,'stale_running'=>$staleRunning,'data_mutation_possible'=>$dataMutationPossible,'automatic_rollback'=>false,'operator_action'=>$action];
        $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $needsRecovery?self::FAILURE:self::SUCCESS;
    }
}
