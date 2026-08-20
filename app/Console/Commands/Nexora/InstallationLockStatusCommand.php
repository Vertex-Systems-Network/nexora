<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\InstallationState;
use Illuminate\Console\Command;

final class InstallationLockStatusCommand extends Command
{
    protected $signature = 'nexora:install:lock-status
        {--assert-valid : Exit non-zero unless a valid installation lock exists}';

    protected $description = 'Inspect the permanent Nexora installation lock and its integrity seal.';

    public function handle(InstallationState $state): int
    {
        $inspection = $state->inspect();
        $payload = [
            'status' => $inspection['status'] ?? 'unknown',
            'exists' => (bool) ($inspection['exists'] ?? false),
            'valid' => (bool) ($inspection['valid'] ?? false),
            'sealed' => (bool) ($inspection['sealed'] ?? false),
            'schema' => $inspection['schema'] ?? null,
            'lock_path' => $state->lockPath(),
            'errors' => $inspection['errors'] ?? [],
        ];

        $this->line(json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        if ((bool) $this->option('assert-valid')) {
            return ($inspection['valid'] ?? false) === true
                ? self::SUCCESS
                : self::FAILURE;
        }

        return ($inspection['status'] ?? 'missing') === 'invalid'
            ? self::FAILURE
            : self::SUCCESS;
    }
}
