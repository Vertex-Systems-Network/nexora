<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimeProcessStatusCommand extends Command
{
    protected $signature='nexora:runtime:process-status {--assert-installed : Require installed process-policy lineage match} {--assert-live : Require configured live web/queue/scheduler role quorum}';
    protected $description='Inspect Nexora process-role policy and live role leases without starting or stopping processes.';
    public function handle(RuntimeProcessPlane $processes,InstallationState $installation): int
    {
        $state=$processes->current((bool)$this->option('assert-live'));$metadata=$installation->metadata();$expected=is_array($metadata)?strtolower(trim((string)($metadata['runtime_process_fingerprint']??''))):'';$match=$expected===''||hash_equals($expected,(string)$state['fingerprint']);
        $state['installed_runtime_process_fingerprint']=$expected!==''?$expected:null;$state['installed_match']=$match;
        if($this->option('assert-installed')&&($expected===''||!$match))$state['status']='fail';
        $this->line(json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return ($state['status']??'fail')==='pass'?self::SUCCESS:self::FAILURE;
    }
}
