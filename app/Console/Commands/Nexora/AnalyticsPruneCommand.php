<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Models\AnalyticsEvent;
use App\Models\SearchQueryLog;
use App\Nexora\Foundation\Contracts\SettingsContract;
use Illuminate\Console\Command;

final class AnalyticsPruneCommand extends Command
{
    protected $signature = 'nexora:analytics:prune {--events-days= : Raw page-view retention days} {--search-days= : Search-query retention days}';
    protected $description = 'Prune raw Nexora analytics/search-demand events while keeping daily aggregate metrics.';

    public function handle(SettingsContract $settings): int
    {
        $eventDays = max(7, min(365, (int) ($this->option('events-days') ?: $settings->get('analytics.raw_retention_days', 90))));
        $searchDays = max(7, min(365, (int) ($this->option('search-days') ?: $settings->get('analytics.search_retention_days', 180))));
        $events = AnalyticsEvent::query()->where('occurred_at', '<', now()->subDays($eventDays))->delete();
        $queries = SearchQueryLog::query()->where('searched_at', '<', now()->subDays($searchDays))->delete();
        $this->info("Pruned {$events} raw analytics events older than {$eventDays} days and {$queries} search logs older than {$searchDays} days.");
        return self::SUCCESS;
    }
}
