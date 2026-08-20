<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimeResourceStatusCommand extends Command
{
    protected $signature='nexora:runtime:resource-status {--deep : Run live memory/disk/file-descriptor capacity probes} {--assert-installed : Compare policy fingerprint with installation lineage}';
    protected $description='Inspect the non-secret Nexora runtime resource policy and live capacity envelope.';

    public function handle(RuntimeResourceEnvelopeIdentity $resources,InstallationState $installation): int
    {
        $state=$resources->current((bool)$this->option('deep'));$installed=$installation->metadata();$expected=is_array($installed)?strtolower(trim((string)($installed['runtime_resource_fingerprint']??''))):'';
        $matches=$expected===''||hash_equals($expected,(string)($state['fingerprint']??''));
        $payload=['state'=>$state,'installed_resource_fingerprint'=>$expected!==''?$expected:null,'installed_match'=>$matches];
        $this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if((bool)$this->option('assert-installed')&&!$matches)return self::FAILURE;
        return ($state['status']??null)==='pass'?self::SUCCESS:self::FAILURE;
    }
}
