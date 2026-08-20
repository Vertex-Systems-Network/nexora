<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeKeyRotationService;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class RuntimeEnvironmentStatusCommand extends Command
{
    protected $signature='nexora:runtime:environment-status';
    protected $description='Inspect the non-secret runtime environment compatibility fingerprint, installed binding, node convergence and key-rotation state without mutating configuration.';
    public function handle(RuntimeEnvironmentIdentity $environment,InstallationState $installation,RuntimeKeyRotationService $rotation): int
    {
        $current=$environment->publicStatus();$installed=$installation->metadata()??[];$nodes=[];
        if(Schema::hasTable('nx_runtime_nodes')){foreach(RuntimeNode::query()->orderBy('node_key')->get() as $node){$m=is_array($node->metadata)?$node->metadata:[];$nodes[]=['node_key'=>(string)$node->node_key,'status'=>(string)$node->status,'version'=>(string)$node->version,'environment_fingerprint'=>$m['runtime_environment_fingerprint']??null];}}
        $payload=['schema'=>1,'status'=>'observed','current'=>$current,'installed_environment_fingerprint'=>$installed['runtime_environment_fingerprint']??null,'installed_key_fingerprint'=>$installed['key_fingerprint']??null,'key_rotation_receipt'=>$rotation->read()!==null?'present':'absent','key_rotation_validation'=>$rotation->read()!==null?$rotation->validate():[],'cluster_rotation_status'=>$rotation->clusterStatus(),'nodes'=>$nodes,'mutation_performed'=>false];
        $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return self::SUCCESS;
    }
}
