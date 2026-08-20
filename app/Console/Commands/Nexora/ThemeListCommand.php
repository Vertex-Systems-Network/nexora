<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\Theme;
use Illuminate\Console\Command;

final class ThemeListCommand extends Command
{
    protected $signature = 'nexora:theme:list';
    protected $description = 'List installed Nexora themes and versions.';

    public function handle(): int
    {
        $rows = Theme::query()->with(['currentVersion', 'versions'])->orderByDesc('status')->orderBy('name')->get()->map(static fn (Theme $theme): array => [
            $theme->identifier,
            $theme->name,
            $theme->status,
            $theme->currentVersion?->version ?? '—',
            $theme->versions->pluck('version')->implode(', '),
            $theme->is_builtin ? 'built-in' : 'package',
        ])->all();
        $this->table(['Identifier', 'Name', 'Status', 'Active version', 'Installed versions', 'Source'], $rows);
        return self::SUCCESS;
    }
}
