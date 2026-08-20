<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Database;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ConcurrencyGuard
{
    /** @template T @param Closure():T $callback @return T */
    public function transaction(Closure $callback, ?int $attempts = null): mixed
    {
        $attempts ??= (int) config('nexora-concurrency.transaction_attempts', 3);
        return DB::transaction($callback, max(1, min(10, $attempts)));
    }

    /** @template T @param Closure():T $callback @return T */
    public function mutex(string $name, Closure $callback, ?int $attempts = null): mixed
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 160 || preg_match('/^[A-Za-z0-9._:-]+$/', $name) !== 1) {
            throw new InvalidArgumentException('Concurrency mutex names must be 1-160 portable identifier characters.');
        }

        return $this->transaction(function () use ($name, $callback): mixed {
            DB::table('nx_concurrency_mutexes')->insertOrIgnore([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('nx_concurrency_mutexes')->where('name', $name)->lockForUpdate()->first();
            return $callback();
        }, $attempts);
    }

    public function isUniqueViolation(QueryException $exception): bool
    {
        $state = strtoupper((string) ($exception->errorInfo[0] ?? $exception->getCode()));
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        if ($state === '23505') return true; // PostgreSQL unique_violation.
        if ($state === '23000' && in_array($driverCode, [19, 1062, 2601, 2627], true)) return true;

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'unique index');
    }
}
