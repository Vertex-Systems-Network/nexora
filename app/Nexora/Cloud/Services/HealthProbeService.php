<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class HealthProbeService
{
    public function __construct(private NodeManager $nodes, private ObjectStorageContract $storage, private RuntimeVersionGuard $versions) {}

    /** @return array{ready:bool,checks:array<int,array{name:string,status:string,duration_ms:float}>} */
    public function readiness(bool $deep = false): array
    {
        $checks = [];
        $checks[] = $this->probe('node', function (): void {
            if (! $this->nodes->isReady()) throw new \RuntimeException('Node is draining or in maintenance mode.');
        });
        $checks[] = $this->probe('runtime_version', fn () => $this->versions->assertCompatible());
        $checks[] = $this->probe('database', fn () => DB::select('select 1'));
        $checks[] = $this->probe('cache', function (): void {
            $key = 'nexora:readiness:'.bin2hex(random_bytes(4));
            Cache::put($key, 'ok', 10);
            if (Cache::get($key) !== 'ok') throw new \RuntimeException('Cache round trip failed.');
            Cache::forget($key);
        });

        if ($deep) {
            $checks[] = $this->probe('object_storage', function (): void {
                $path = 'nexora/health/'.bin2hex(random_bytes(8)).'.probe';
                $this->storage->put($path, 'ok', ['visibility' => 'private']);
                if (! $this->storage->exists($path)) throw new \RuntimeException('Object storage write verification failed.');
                $this->storage->delete($path);
            });
        }

        return ['ready' => collect($checks)->every(fn (array $c): bool => $c['status'] === 'healthy'), 'checks' => $checks];
    }

    private function probe(string $name, callable $probe): array
    {
        $started = microtime(true);
        try {
            $probe();
            return ['name' => $name, 'status' => 'healthy', 'duration_ms' => round((microtime(true) - $started) * 1000, 2)];
        } catch (\Throwable $e) {
            report($e);
            return ['name' => $name, 'status' => 'unhealthy', 'duration_ms' => round((microtime(true) - $started) * 1000, 2)];
        }
    }
}
