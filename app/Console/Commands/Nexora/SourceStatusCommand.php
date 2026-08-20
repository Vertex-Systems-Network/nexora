<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use App\Nexora\Installation\SourceActivationHandshake;
use App\Nexora\Installation\SourceActivationIdentity;
use Illuminate\Console\Command;

final class SourceStatusCommand extends Command
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    protected $signature = 'nexora:source:status
        {--assert-current : Exit non-zero when this PHP process does not match the packaged critical source set}
        {--require-web-ack : Also require a current web-process acknowledgement for the latest CLI activation nonce}
        {--web-token : Print only the current one-time web acknowledgement token for local activation tooling}';

    protected $description = 'Inspect exact Nexora source-set integrity and optional CLI-to-web activation acknowledgement.';

    public function handle(
        SourceActivationIdentity $identity,
        SourceActivationHandshake $handshake,
    ): int {
        $source = $identity->inspect();

        if ((bool) $this->option('web-token')) {
            $token = $handshake->webAckToken($source);
            if (! is_string($token) || $token === '') {
                return self::FAILURE;
            }

            $this->line($token);

            return self::SUCCESS;
        }

        $activation = $handshake->inspect($source);
        $state = [
            ...$source,
            'activation_handshake' => $activation,
        ];

        $this->line(json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        $sourceCurrent = ($source['status'] ?? 'fail') === 'pass';
        $webAcknowledged = ($activation['status'] ?? 'pending') === 'pass';

        if ((bool) $this->option('require-web-ack')) {
            return $sourceCurrent && $webAcknowledged
                ? self::SUCCESS
                : self::FAILURE;
        }

        if ((bool) $this->option('assert-current')) {
            return $sourceCurrent ? self::SUCCESS : self::FAILURE;
        }

        return $sourceCurrent ? self::SUCCESS : self::FAILURE;
    }
}
