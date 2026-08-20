<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class RuntimeServiceStatusCommand extends Command
{
    protected $signature='nexora:runtime:service-status {--deep : Run non-destructive cache/Redis/queue/mail/CA probes} {--assert-installed : Fail unless current service fingerprint matches installation metadata}';
    protected $description='Inspect Nexora cache/session/queue/mail/proxy/TLS service data-plane identity without exposing secrets.';
    public function handle(RuntimeServiceDataPlaneIdentity $identity,InstallationState $installation): int
    {
        try{$status=$identity->current((bool)$this->option('deep'));$installed=$installation->metadata();$expected=is_array($installed)?strtolower(trim((string)($installed['runtime_service_fingerprint']??''))):'';$current=(string)$status['fingerprint'];$matches=$expected===''?null:hash_equals($expected,$current);$payload=['current'=>$status,'installed_runtime_service_fingerprint'=>$expected!==''?$expected:null,'installed_match'=>$matches];$this->line(json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));if(($status['status']??null)!=='pass')return self::FAILURE;if($this->option('assert-installed')&&$matches!==true){$this->error('Runtime service data-plane does not match installed metadata.');return self::FAILURE;}return self::SUCCESS;}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
