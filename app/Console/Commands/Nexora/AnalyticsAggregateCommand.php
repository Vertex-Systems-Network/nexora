<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Discovery\Analytics\AnalyticsAggregator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class AnalyticsAggregateCommand extends Command
{
    protected $signature = 'nexora:analytics:aggregate {--date= : YYYY-MM-DD; defaults to today}';
    protected $description = 'Aggregate privacy-aware Nexora analytics events into daily metrics.';
    public function handle(AnalyticsAggregator $aggregator): int
    {
        $raw = (string) ($this->option('date') ?: now()->toDateString());
        try { $date = CarbonImmutable::parse($raw); } catch (\Throwable) { $this->error('Invalid --date value.'); return self::FAILURE; }
        $result = $aggregator->aggregate($date);
        $this->info("Aggregated {$result['metrics']} metric rows for {$result['date']}.");
        return self::SUCCESS;
    }
}
