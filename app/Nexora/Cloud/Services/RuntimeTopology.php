<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use Illuminate\Support\Facades\DB;

final class RuntimeTopology
{
    public function __construct(private ObjectStorageContract $storage, private NodeIdentity $identity, private RuntimeStorageDataPlaneIdentity $storageIdentity) {}

    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $cache = (string) config('cache.default', 'file');
        $queue = (string) config('queue.default', 'sync');
        $session = (string) config('session.driver', 'file');
        $storage = $this->storage->capabilities();
        $storageDataPlane=$this->storageIdentity->current(false);

        $warnings = [];
        if ($queue === 'sync') $warnings[] = 'Queue driver is sync; background work executes inside web requests.';
        if (in_array($cache, ['array', 'file'], true)) $warnings[] = 'Cache driver is node-local; use a shared atomic-lock capable cache for horizontal scaling.';
        if (in_array($session, ['array', 'file', 'cookie'], true)) $warnings[] = 'Session driver is not shared server-side; multi-node login continuity requires sticky sessions or a shared session store.';
        if (! ($storage['shared'] ?? false)) $warnings[] = 'Object storage is node-local; horizontally scaled media/backups require shared object storage.';

        return [
            'node' => ['key' => $this->identity->key(), 'hostname' => $this->identity->hostname()],
            'database' => ['connection' => (string) config('database.default'), 'driver' => DB::connection()->getDriverName()],
            'cache' => ['store' => $cache, 'shared_candidate' => ! in_array($cache, ['array', 'file'], true)],
            'queue' => ['connection' => $queue, 'async' => $queue !== 'sync', 'queues' => array_values((array) config('nexora_cloud.queues', []))],
            'session' => ['driver' => $session, 'shared_candidate' => ! in_array($session, ['array', 'file', 'cookie'], true)],
            'object_storage' => $storage,
            'storage_data_plane'=>['fingerprint'=>$storageDataPlane['fingerprint']??null,'roles'=>$storageDataPlane['roles']??[]],
            'scheduler' => ['leadership' => 'database-lease', 'lease_seconds' => (int) config('nexora_cloud.scheduler_lease_seconds', 90)],
            'warnings' => $warnings,
            'ha_ready' => $warnings === [],
        ];
    }
}
