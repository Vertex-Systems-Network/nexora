<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Upgrade\UpgradeCompatibilityService;
use Illuminate\Console\Command;

final class UpgradePreflightCommand extends Command
{
    protected $signature='nexora:upgrade:preflight';
    protected $description='Assess current Nexora installation, migrations, extensions and themes before an in-place platform upgrade.';
    public function handle(UpgradeCompatibilityService $compatibility): int
    {
        $result=$compatibility->assess();
        $this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return ($result['status']??null)==='pass' ? self::SUCCESS : self::FAILURE;
    }
}
