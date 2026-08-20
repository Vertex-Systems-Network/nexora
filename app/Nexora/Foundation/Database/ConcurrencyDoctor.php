<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ConcurrencyDoctor
{
    /** @return array{ok:bool,driver:string,attempts:int,checks:array<int,array{name:string,ok:bool,message:string}>} */
    public function inspect(): array
    {
        $driver = (string) DB::connection()->getDriverName();
        $attempts = (int) config('nexora-concurrency.transaction_attempts', 3);
        $checks = [];

        $this->check($checks, 'supported_driver', in_array($driver, (array) config('nexora-concurrency.supported_drivers', []), true), "Database driver: {$driver}");
        $this->check($checks, 'deadlock_retry_budget', $attempts >= 2 && $attempts <= 10, "Transaction attempts: {$attempts}");

        $this->check($checks, 'mutex_table', Schema::hasTable('nx_concurrency_mutexes'), 'Portable transaction mutex table is available.');

        foreach ([
            ['nx_automation_events', ['idempotency_key']],
            ['nx_webhook_receipts', ['webhook_endpoint_id', 'idempotency_key']],
            ['nx_webhook_deliveries', ['idempotency_key']],
            ['nx_commerce_payment_transactions', ['idempotency_key']],
            ['nx_commerce_refunds', ['idempotency_key']],
            ['nx_newsletter_deliveries', ['campaign_id', 'subscriber_id']],
        ] as [$table, $columns]) {
            if (! Schema::hasTable($table)) continue;
            $missing = array_values(array_filter($columns, static fn (string $column): bool => ! Schema::hasColumn($table, $column)));
            $this->check($checks, 'columns:'.$table, $missing === [], $missing === [] ? "{$table} concurrency columns present." : "{$table} missing: ".implode(', ', $missing));
        }

        return [
            'ok' => ! in_array(false, array_column($checks, 'ok'), true),
            'driver' => $driver,
            'attempts' => $attempts,
            'checks' => $checks,
        ];
    }

    /** @param array<int,array{name:string,ok:bool,message:string}> $checks */
    private function check(array &$checks, string $name, bool $ok, string $message): void
    {
        $checks[] = compact('name', 'ok', 'message');
    }
}
