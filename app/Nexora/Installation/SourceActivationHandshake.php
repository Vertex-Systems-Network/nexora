<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

final class SourceActivationHandshake
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    public function __construct(private readonly AtomicFileWriter $files)
    {
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    public function issueCliActivation(array $source): array
    {
        $now = time();
        $webToken = bin2hex(random_bytes(32));
        $receipt = [
            'schema' => 3,
            'status' => ($source['status'] ?? 'fail') === 'pass'
                ? 'pending-web-ack'
                : 'invalid-source',
            'nonce' => bin2hex(random_bytes(24)),
            'web_ack_token_sha256' => hash('sha256', $webToken),
            'platform_version' => $source['platform_version'] ?? null,
            'installer_protocol' => $source['running_protocol'] ?? null,
            'source_generation' => $source['running_generation'] ?? null,
            'source_set_fingerprint' => $source['source_set_fingerprint'] ?? null,
            'runtime_class_fingerprint' => $source['runtime_class_fingerprint'] ?? null,
            'runtime_classes_matched' => $source['runtime_classes_matched'] ?? 0,
            'runtime_classes_total' => $source['runtime_classes_total'] ?? 0,
            'critical_source_files' => $source['critical_source_files'] ?? 0,
            'php_sapi' => PHP_SAPI,
            'php_binary' => PHP_BINARY,
            'activated_at' => gmdate(DATE_ATOM, $now),
            'activated_epoch' => $now,
            'expires_at' => gmdate(DATE_ATOM, $now + $this->ttl()),
            'expires_epoch' => $now + $this->ttl(),
        ];
        $receipt['receipt_sha256'] = $this->seal($receipt);

        $this->write($this->receiptPath(), $receipt);
        $this->files->write($this->tokenPath(), $webToken.PHP_EOL, 0755, 0600);
        @unlink($this->ackPath());

        return $receipt;
    }

    /**
     * Authorize one exact web-process acknowledgement. The bearer token is
     * intentionally single-use and is deleted immediately after a successful
     * acknowledgement.
     *
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public function acknowledgeWeb(array $source, ?string $token): array
    {
        $state = $this->inspect($source);
        $receipt = is_array($state['cli_receipt'] ?? null)
            ? $state['cli_receipt']
            : null;
        $provided = trim((string) $token);
        $authorized = false;

        if (($state['cli_receipt_valid'] ?? false) === true
            && is_array($receipt)
            && $provided !== '') {
            $expected = strtolower(trim((string) ($receipt['web_ack_token_sha256'] ?? '')));
            $authorized = preg_match('/^[a-f0-9]{64}$/', $expected) === 1
                && hash_equals($expected, hash('sha256', $provided));
        }

        if (! $authorized) {
            $state['acknowledgement_authorized'] = false;
            $state['errors'] = array_values(array_unique([
                ...(array) ($state['errors'] ?? []),
                'A valid one-time CLI activation token is required to acknowledge the web process.',
            ]));

            return $state;
        }

        $ack = [
            'schema' => 2,
            'status' => 'acknowledged',
            'nonce' => $receipt['nonce'] ?? null,
            'platform_version' => $source['platform_version'] ?? null,
            'installer_protocol' => $source['running_protocol'] ?? null,
            'source_generation' => $source['running_generation'] ?? null,
            'source_set_fingerprint' => $source['source_set_fingerprint'] ?? null,
            'runtime_class_fingerprint' => $source['runtime_class_fingerprint'] ?? null,
            'runtime_classes_matched' => $source['runtime_classes_matched'] ?? 0,
            'runtime_classes_total' => $source['runtime_classes_total'] ?? 0,
            'php_sapi' => PHP_SAPI,
            'acknowledged_at' => gmdate(DATE_ATOM),
            'acknowledged_epoch' => time(),
        ];
        $ack['ack_sha256'] = $this->seal($ack);
        $this->write($this->ackPath(), $ack);
        @unlink($this->tokenPath());

        $state = $this->inspect($source);
        $state['acknowledgement_authorized'] = true;

        return $state;
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    public function inspect(array $source): array
    {
        $errors = [];
        $receipt = $this->read($this->receiptPath());
        $ack = $this->read($this->ackPath());
        $receiptValid = false;
        $ackValid = false;

        if ($receipt === null) {
            $errors[] = 'CLI source activation receipt is missing. Run `php artisan nexora:source:activate --assert-current`.';
        } else {
            $receiptValid = $this->validSeal($receipt, 'receipt_sha256')
                && (int) ($receipt['expires_epoch'] ?? 0) >= time()
                && $this->sameRuntimeIdentity($receipt, $source);

            if (! $receiptValid) {
                $errors[] = 'CLI source activation receipt is stale, expired, tampered, or belongs to another source/runtime generation.';
            }
        }

        if ($ack !== null && $receiptValid) {
            $ackValid = $this->validSeal($ack, 'ack_sha256')
                && hash_equals((string) ($receipt['nonce'] ?? ''), (string) ($ack['nonce'] ?? ''))
                && $this->sameRuntimeIdentity($ack, $source);

            if (! $ackValid) {
                $errors[] = 'Web source acknowledgement does not match the current CLI activation nonce/source/runtime set.';
            }
        } elseif ($receiptValid) {
            $errors[] = 'Web process has not acknowledged the current CLI source activation yet.';
        }

        return [
            'status' => $receiptValid && $ackValid ? 'pass' : 'pending',
            'current' => $receiptValid && $ackValid,
            'cli_receipt_valid' => $receiptValid,
            'web_ack_valid' => $ackValid,
            'web_ack_token_available' => $receiptValid && is_file($this->tokenPath()),
            'nonce' => $receipt['nonce'] ?? null,
            'source_set_fingerprint' => $source['source_set_fingerprint'] ?? null,
            'runtime_class_fingerprint' => $source['runtime_class_fingerprint'] ?? null,
            'runtime_classes_matched' => $source['runtime_classes_matched'] ?? 0,
            'runtime_classes_total' => $source['runtime_classes_total'] ?? 0,
            'cli_receipt' => $receipt,
            'web_ack' => $ack,
            'receipt_path' => $this->receiptPath(),
            'web_ack_path' => $this->ackPath(),
            'errors' => $errors,
        ];
    }

    /**
     * Return the local one-time token only when the CLI receipt still belongs
     * to this exact source/runtime generation. This method is for local CLI
     * tooling; the token is never included in the public HTTP status payload.
     *
     * @param array<string,mixed> $source
     */
    public function webAckToken(array $source): ?string
    {
        $state = $this->inspect($source);
        if (($state['cli_receipt_valid'] ?? false) !== true) {
            return null;
        }

        $path = $this->tokenPath();
        if (! is_file($path) || is_link($path)) {
            return null;
        }

        $token = trim((string) file_get_contents($path));
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $receipt = (array) ($state['cli_receipt'] ?? []);
        $expected = strtolower(trim((string) ($receipt['web_ack_token_sha256'] ?? '')));

        return preg_match('/^[a-f0-9]{64}$/', $expected) === 1
            && hash_equals($expected, hash('sha256', $token))
                ? $token
                : null;
    }

    /** @param array<string,mixed> $record @param array<string,mixed> $source */
    private function sameRuntimeIdentity(array $record, array $source): bool
    {
        return hash_equals(
            (string) ($record['source_set_fingerprint'] ?? ''),
            (string) ($source['source_set_fingerprint'] ?? ''),
        )
            && hash_equals(
                (string) ($record['runtime_class_fingerprint'] ?? ''),
                (string) ($source['runtime_class_fingerprint'] ?? ''),
            )
            && (int) ($record['runtime_classes_matched'] ?? -1)
                === (int) ($source['runtime_classes_matched'] ?? 0)
            && (int) ($record['runtime_classes_total'] ?? -1)
                === (int) ($source['runtime_classes_total'] ?? 0)
            && ($record['platform_version'] ?? null) === ($source['platform_version'] ?? null)
            && ($record['installer_protocol'] ?? null) === ($source['running_protocol'] ?? null)
            && ($record['source_generation'] ?? null) === ($source['running_generation'] ?? null);
    }

    private function receiptPath(): string
    {
        return (string) config(
            'installer.source.activation_receipt_path',
            storage_path('app/nexora/source-activation/cli-activation.json'),
        );
    }

    private function ackPath(): string
    {
        return (string) config(
            'installer.source.web_ack_path',
            storage_path('app/nexora/source-activation/web-ack.json'),
        );
    }

    private function tokenPath(): string
    {
        return (string) config(
            'installer.source.web_ack_token_path',
            storage_path('app/nexora/source-activation/web-ack.token'),
        );
    }

    private function ttl(): int
    {
        return max(120, (int) config('installer.source.activation_ttl_seconds', 900));
    }

    /** @param array<string,mixed> $payload */
    private function write(string $path, array $payload): void
    {
        $this->files->write(
            $path,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ).PHP_EOL,
            0755,
            0600,
        );
    }

    /** @return array<string,mixed>|null */
    private function read(string $path): ?array
    {
        if (! is_file($path) || is_link($path)) {
            return null;
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                128,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string,mixed> $payload */
    private function validSeal(array $payload, string $key): bool
    {
        $stored = strtolower(trim((string) ($payload[$key] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $stored) !== 1) {
            return false;
        }

        unset($payload[$key]);

        return hash_equals($stored, $this->seal($payload));
    }

    /** @param array<string,mixed> $payload */
    private function seal(array $payload): string
    {
        ksort($payload, SORT_STRING);

        return hash(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }
}
