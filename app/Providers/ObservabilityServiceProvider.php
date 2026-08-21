<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\PruneObservability;
use App\Nexora\Cloud\Services\ClusterLeadership;
use App\Nexora\Observability\Services\ObservabilityRecorder;
use App\Nexora\Observability\Services\ObservabilityRetentionService;
use App\Nexora\Observability\Services\TelemetrySanitizer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelemetrySanitizer::class);
        $this->app->singleton(ObservabilityRecorder::class);
        $this->app->singleton(ObservabilityRetentionService::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneObservability::class]);
        }
    }

    public function boot(Schedule $schedule): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $time = (string) config('nexora_observability.prune_time', '04:50');
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            $time = '04:50';
        }

        $schedule->command('nexora:observability:prune')
            ->dailyAt($time)
            ->withoutOverlapping()
            ->when(static fn (): bool => app(ClusterLeadership::class)->isSchedulerLeader());
    }
}
