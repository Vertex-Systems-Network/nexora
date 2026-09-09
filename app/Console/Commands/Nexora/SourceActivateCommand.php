<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\SourceActivationHandshake;
use App\Nexora\Installation\SourceActivationIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class SourceActivateCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    protected $signature = 'nexora:source:activate
        {--assert-current : Fail if this CLI process is not executing the packaged critical source set after cache cleanup}';

    protected $description = 'Clear Laravel caches and issue a CLI activation nonce and one-time token that the active web PHP process must securely acknowledge.';

    public function handle(
        SourceActivationIdentity $identity,
        SourceActivationHandshake $handshake,
    ): int {
        $this->info('Clearing Laravel bootstrap/application caches…');
        $exit = Artisan::call('optimize:clear');
        if ($exit !== 0) {
            $this->error(trim(Artisan::output()));
            return self::FAILURE;
        }

        clearstatcache(true);
        $cliOpcacheReset = null;
        if (function_exists('opcache_reset')) {
            try {
                $cliOpcacheReset = @opcache_reset();
            } catch (\Throwable) {
                $cliOpcacheReset = false;
            }
        }

        $state = $identity->inspect();
        if (($state['status'] ?? 'fail') !== 'pass') {
            $this->line(json_encode(
                $state,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));

            if ((bool) $this->option('assert-current')) {
                throw new RuntimeException(
                    'CLI source activation cannot issue a web acknowledgement nonce because the critical source set is not current.',
                );
            }

            return self::FAILURE;
        }

        $receipt = $handshake->issueCliActivation($state);
        $receipt['cli_opcache_reset'] = $cliOpcacheReset;
        $receipt['web_process_reload_required'] = true;

        $this->line(json_encode(
            $receipt,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');
        $ackCommand = PHP_OS_FAMILY === 'Windows'
            ? "scripts\\n1-source-web-ack.bat {$baseUrl}"
            : "scripts/n1-source-web-ack.sh {$baseUrl}";

        $this->warn(
            'CLI source is current. Restart/reload the active PHP/web service, run `'.$ackCommand.'`, '
            .'then run `php artisan nexora:source:status --require-web-ack` to prove the web process acknowledged this exact disk source and loaded runtime-class generation.',
        );

        return self::SUCCESS;
    }
}
