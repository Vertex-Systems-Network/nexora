<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use Illuminate\Console\Command;

final class UpgradeNodeStatusCommand extends Command
{
    protected $signature='nexora:upgrade:node-status {status? : active|draining|maintenance} {--confirm= : Must equal SET for mutation}';
    protected $description='Inspect or explicitly change only this runtime node upgrade status; activation is blocked until version/quiescence/upgrade ownership are safe.';
    public function handle(NodeManager $nodes,UpgradeClusterCoordinator $cluster,UpgradeTransactionJournal $journal): int
    {
        $status=$this->argument('status');if($status===null){$this->line(json_encode(['status'=>$nodes->status(),'activation'=>$cluster->activationAssessment()],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
        $status=(string)$status;if(!in_array($status,['active','draining','maintenance'],true)){$this->error('Status must be active, draining or maintenance.');return self::INVALID;}
        if((string)$this->option('confirm')!=='SET'){$this->error('Mutation refused. Pass --confirm=SET.');return self::INVALID;}
        if($status==='active'){
            $tx=$journal->read();if(is_array($tx)&&in_array((string)($tx['status']??''),['running','recovery_required'],true)){$this->error('Activation refused while an upgrade transaction is running or recovery_required.');return self::FAILURE;}
            $assessment=$cluster->activationAssessment();if(($assessment['status']??null)!=='pass'){$this->error('Activation refused: '.implode('; ',(array)($assessment['errors']??[])));return self::FAILURE;}
        }
        $node=$nodes->setStatus($status);$this->line(json_encode(['status'=>$status,'node_key'=>$node?->node_key,'version'=>$node?->version],JSON_UNESCAPED_SLASHES));return self::SUCCESS;
    }
}
