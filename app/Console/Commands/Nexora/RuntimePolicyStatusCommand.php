<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimePolicyStatusCommand extends Command
{
    protected $signature='nexora:runtime:policy-status {--deep : Include effective fail-closed policy invariant checks} {--assert-installed : Require exact installed policy-plane fingerprint}';
    protected $description='Inspect the effective secret-free Nexora runtime policy plane and convergence fingerprint.';
    public function handle(RuntimePolicyPlaneIdentity $policy,InstallationState $installation): int
    {
        $state=$policy->current((bool)$this->option('deep'));$metadata=$installation->metadata()??[];$expected=strtolower(trim((string)($metadata['runtime_policy_fingerprint']??'')));$match=$expected===''||hash_equals($expected,(string)$state['fingerprint']);
        $errors=[];if($this->option('assert-installed')&&($expected===''||!$match))$errors[]=$expected===''?'installed policy-plane fingerprint is missing':'effective policy plane does not match installed lineage';if(($state['status']??null)!=='pass')$errors[]='one or more effective policy invariants are not fail-closed';
        $this->line(json_encode(['status'=>$errors===[]?'pass':'fail','errors'=>$errors,'installed_match'=>$match,'policy'=>$state],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return $errors===[]?self::SUCCESS:self::FAILURE;
    }
}
