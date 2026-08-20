<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Cloud\Contracts\DistributedLockContract;
use Illuminate\Support\Facades\Cache;

final class LaravelDistributedLock implements DistributedLockContract
{
    public function block(string $key, int $ttlSeconds, int $waitSeconds, callable $callback): mixed
    {
        $key = trim($key);
        if ($key === '' || strlen($key) > 180) throw new \InvalidArgumentException('Distributed lock key must be 1-180 characters.');
        $ttlSeconds = max(1, min(3600, $ttlSeconds));
        $waitSeconds = max(0, min(120, $waitSeconds));
        return Cache::lock('nexora:distributed:'.hash('sha256', $key), $ttlSeconds)->block($waitSeconds, $callback);
    }
}
