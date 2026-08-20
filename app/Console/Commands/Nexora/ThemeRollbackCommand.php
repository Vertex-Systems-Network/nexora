<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Illuminate\Console\Command;

final class ThemeRollbackCommand extends Command
{
    protected $signature = 'nexora:theme:rollback';
    protected $description = 'Roll back to the previous Nexora theme activation snapshot.';

    public function handle(ThemeManagerContract $themes): int
    {
        $version = $themes->rollback();
        if ($version === null) {
            $this->warn('No previous activation snapshot is available.');
            return self::FAILURE;
        }
        $version->loadMissing('theme');
        $this->info("Rolled back to {$version->theme?->name} {$version->version}.");
        return self::SUCCESS;
    }
}
