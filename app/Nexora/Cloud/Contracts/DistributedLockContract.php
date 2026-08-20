<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Contracts;

interface DistributedLockContract
{
    /** @template T @param callable():T $callback @return T */
    public function block(string $key, int $ttlSeconds, int $waitSeconds, callable $callback): mixed;
}
