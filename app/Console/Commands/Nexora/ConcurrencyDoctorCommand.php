<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Database\ConcurrencyDoctor;
use Illuminate\Console\Command;

final class ConcurrencyDoctorCommand extends Command
{
    protected $signature = 'nexora:concurrency:doctor';
    protected $description = 'Verify Nexora database transaction, idempotency and concurrency prerequisites.';

    public function handle(ConcurrencyDoctor $doctor): int
    {
        $result = $doctor->inspect();
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
