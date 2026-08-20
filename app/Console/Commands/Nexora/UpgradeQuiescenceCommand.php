<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeClusterCoordinator;
use Illuminate\Console\Command;

final class UpgradeQuiescenceCommand extends Command
{
    protected $signature='nexora:upgrade:quiescence {--wait : Wait up to the configured quiescence timeout for this node}';
    protected $description='Inspect in-flight web/queue/scheduler activity and queue backlog before schema mutation.';
    public function handle(UpgradeClusterCoordinator $cluster): int
    {
        try{$result=$this->option('wait')?$cluster->waitForCurrentQuiescence():$cluster->currentQuiescenceStatus();$this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return ($result['status']??null)==='pass'?self::SUCCESS:self::FAILURE;}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
