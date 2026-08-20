<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use Illuminate\Console\Command;

final class RuntimeEngineStatusCommand extends Command
{
    protected $signature='nexora:runtime:engine-status {--deep : Include extension/version/INI/process material used for compatibility diagnostics}';
    protected $description='Inspect the non-secret PHP runtime-engine compatibility identity used for node, queue and upgrade fencing.';
    public function handle(RuntimeEngineIdentity $engine): int
    {
        $status=$engine->publicStatus((bool)$this->option('deep'));
        $this->line(json_encode($status,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return ($status['status']??null)==='pass'?self::SUCCESS:self::FAILURE;
    }
}
