<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Runtime\RuntimeLimitsDoctor;
use Illuminate\Console\Command;

final class RuntimeDoctorCommand extends Command
{
    protected $signature = 'nexora:runtime:doctor';
    protected $description = 'Verify PHP request ceilings, trusted-proxy policy and queue worker timeout/retry safety.';

    public function handle(RuntimeLimitsDoctor $doctor): int
    {
        $result = $doctor->inspect();
        $this->line(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $result['status'] === 'pass' ? self::SUCCESS : self::FAILURE;
    }
}
