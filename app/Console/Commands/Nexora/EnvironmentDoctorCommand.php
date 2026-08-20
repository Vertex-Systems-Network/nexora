<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Environment\EnvironmentDoctor;
use Illuminate\Console\Command;

final class EnvironmentDoctorCommand extends Command
{
    protected $signature = 'nexora:environment:doctor {--strict : Return failure when warnings are present} {--production : Enforce installed/production policy even without an installed lock}';
    protected $description = 'Audit Nexora environment source, config-cache freshness and production configuration without exposing secrets.';

    public function handle(EnvironmentDoctor $doctor): int
    {
        $result = $doctor->inspect((bool) $this->option('production'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if (($result['status'] ?? null) === 'fail') return self::FAILURE;
        if ((bool) $this->option('strict') && ($result['warnings'] ?? []) !== []) return self::FAILURE;
        return self::SUCCESS;
    }
}
