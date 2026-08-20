<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class RuntimeActivationStatusCommand extends Command
{
    protected $signature='nexora:runtime:activation-status {--deep : Also recompute deployment source/material hashes}';
    protected $description='Inspect runtime activation epoch, framework-cache snapshot, process compatibility, OPCache policy and cluster activation convergence without mutating state.';

    public function handle(RuntimeActivationIdentity $activation,RuntimeDeploymentIdentity $deployment): int
    {
        try{$status=$activation->publicStatus();$deep=$this->option('deep')?$deployment->deepVerify():null;$nodes=[];
            if(Schema::hasTable('nx_runtime_nodes'))foreach(RuntimeNode::query()->orderBy('node_key')->get() as $node){$m=is_array($node->metadata)?$node->metadata:[];$nodes[]=['node_key'=>(string)$node->node_key,'status'=>(string)$node->status,'version'=>(string)$node->version,'activation_epoch'=>$m['activation_epoch']??null,'runtime_activation_fingerprint'=>$m['runtime_activation_fingerprint']??null,'process_activation_epoch'=>$m['process_activation_epoch']??null];}
            $payload=['schema'=>1,'status'=>'observed','activation'=>$status,'deep_deployment'=>$deep,'nodes'=>$nodes,'mutation_performed'=>false];$this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            if(($status['status']??null)!=='pass')return self::FAILURE;if(is_array($deep)&&!($deep['ok']??false))return self::FAILURE;return self::SUCCESS;
        }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
