<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\Theme;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use Illuminate\Console\Command;

final class ThemeActivateCommand extends Command
{
    protected $signature = 'nexora:theme:activate {identifier} {--version= : Exact installed semantic version}';
    protected $description = 'Activate an installed Nexora theme version.';

    public function handle(ThemeManagerContract $themes): int
    {
        $theme = Theme::query()->where('identifier', (string) $this->argument('identifier'))->first();
        if ($theme === null) {
            $this->error('Theme is not installed.');
            return self::FAILURE;
        }
        $query = $theme->versions()->latest('installed_at');
        $requested = trim((string) $this->option('version'));
        if ($requested !== '') $query->where('version', $requested);
        $version = $query->first();
        if ($version === null) {
            $this->error('Requested installed theme version was not found.');
            return self::FAILURE;
        }
        $themes->activate($version, null, 'cli-activate', 'Activated through Nexora CLI.');
        $this->info("Activated {$theme->name} {$version->version}.");
        return self::SUCCESS;
    }
}
