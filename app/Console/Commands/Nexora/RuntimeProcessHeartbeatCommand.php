<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use Illuminate\Console\Command;

final class RuntimeProcessHeartbeatCommand extends Command
{
    protected $signature='nexora:runtime:process-heartbeat {role : web, queue or scheduler}';
    protected $description='Renew the current node runtime-process role lease.';
    public function handle(RuntimeProcessPlane $processes): int
    {
        try{$role=(string)$this->argument('role');$ok=$processes->heartbeat($role);$this->line(json_encode(['role'=>$role,'heartbeat'=>$ok?'recorded':'refused','process_policy_fingerprint'=>$processes->fingerprintValue()],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));return $ok?self::SUCCESS:self::FAILURE;}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
    }
}
