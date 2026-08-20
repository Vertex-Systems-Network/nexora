<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class UpgradeClusterStatusCommand extends Command
{
    protected $signature='nexora:upgrade:cluster-status';
    protected $description='Inspect distributed upgrade lease, fresh runtime nodes, activity quiescence and source/target version convergence without mutating cluster state.';
    public function handle(UpgradeClusterCoordinator $cluster,InstallationState $installation): int
    {
        $metadata=$installation->metadata()??[];$source=trim((string)($metadata['version']??''));$target=(string)config('nexora.version','');
        $assessment=$cluster->assess($source,$target);$payload=['source_version'=>$source!==''?$source:null,'target_version'=>$target,'assessment'=>$assessment,'convergence'=>$cluster->convergence($target),'lease'=>$cluster->leaseStatus(),'automatic_peer_drain'=>false];
        $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return ($assessment['status']??null)==='pass'?self::SUCCESS:self::FAILURE;
    }
}
