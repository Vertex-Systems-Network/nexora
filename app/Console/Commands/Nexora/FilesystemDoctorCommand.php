<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Filesystem\FilesystemDoctor;
use Illuminate\Console\Command;

final class FilesystemDoctorCommand extends Command
{
    protected $signature = 'nexora:filesystem:doctor {--no-probe : Do not write temporary atomic probe files}';
    protected $description = 'Verify Nexora writable paths, storage boundaries and atomic filesystem behavior.';

    public function handle(FilesystemDoctor $doctor): int
    {
        $result = $doctor->inspect(! (bool) $this->option('no-probe'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return $result['status'] === 'pass' ? self::SUCCESS : self::FAILURE;
    }
}
