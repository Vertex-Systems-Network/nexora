<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RuntimeMetricsRecorder
{
    public function __construct(private NodeIdentity $identity) {}

    /** @return array<string,float|int> */
    public function snapshot(): array
    {
        if (! Schema::hasTable('nx_runtime_metrics')) return [];

        $metrics = [];
        $dbStart = microtime(true); DB::select('select 1');
        $metrics['runtime.database_latency_ms'] = round((microtime(true) - $dbStart) * 1000, 3);
        $cacheStart = microtime(true); Cache::put('nexora.runtime.metrics.probe', 'ok', 10); Cache::get('nexora.runtime.metrics.probe');
        $metrics['runtime.cache_latency_ms'] = round((microtime(true) - $cacheStart) * 1000, 3);
        $metrics['runtime.queue_backlog'] = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $metrics['runtime.failed_jobs'] = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $metrics['runtime.memory_peak_bytes'] = memory_get_peak_usage(true);

        foreach ($metrics as $name => $value) {
            RuntimeMetric::query()->create([
                'node_key' => $this->identity->key(),
                'metric' => $name,
                'value' => $value,
                'unit' => str_ends_with($name, '_ms') ? 'ms' : (str_ends_with($name, '_bytes') ? 'bytes' : 'count'),
                'tags' => ['environment' => app()->environment()],
                'observed_at' => now(),
            ]);
        }

        return $metrics;
    }
}
