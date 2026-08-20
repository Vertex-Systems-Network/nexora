<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\RuntimePostInstallHandoff;
use Illuminate\Console\Command;

final class RuntimePostInstallStatusCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    protected $signature = 'nexora:runtime:post-install-status
        {--assert-ready : Exit non-zero unless the installed runtime handoff is ready}';

    protected $description = 'Verify sealed installed state against the exact runtime identity used by the next web request.';

    public function handle(RuntimePostInstallHandoff $handoff): int
    {
        $state = $handoff->inspect();
        $this->line(json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        if ((bool) $this->option('assert-ready')) {
            return ($state['ready'] ?? false) === true
                ? self::SUCCESS
                : self::FAILURE;
        }

        return ($state['status'] ?? 'fail') === 'pass'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
