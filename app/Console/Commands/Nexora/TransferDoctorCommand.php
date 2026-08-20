<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Foundation\Transfers\TransferDoctor;
use Illuminate\Console\Command;

final class TransferDoctorCommand extends Command
{
    protected $signature = 'nexora:transfer:doctor {--no-probe : Do not write the bounded transfer probe}';
    protected $description = 'Verify Nexora transfer staging, capacity reserves and bounded streaming behavior.';

    public function handle(TransferDoctor $doctor): int
    {
        $result=$doctor->inspect(! (bool)$this->option('no-probe'));
        $this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $result['status']==='pass'?self::SUCCESS:self::FAILURE;
    }
}
