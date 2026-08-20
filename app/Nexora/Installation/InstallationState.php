<?php

declare(strict_types=1);

namespace App\Nexora\Installation;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use RuntimeException;

final class InstallationState
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';
    private const HASH_KEY = '_lock_sha256';
    private const SCHEMA_KEY = '_lock_schema';

    public function __construct(private readonly AtomicFileWriter $files)
    {
    }

    /**
     * A present lock always closes the installer, even when the lock is corrupt.
     * Treating an invalid lock as "not installed" could reopen destructive setup
     * controls against an already provisioned database.
     */
    public function isInstalled(): bool
    {
        if ((bool) config('installer.bypass', false)) {
            return true;
        }

        return is_file($this->lockPath());
    }

    public function lockPath(): string
    {
        return (string) config(
            'installer.lock_path',
            base_path('storage/app/nexora/installed.lock'),
        );
    }

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $path = $this->lockPath();

        if ((bool) config('installer.bypass', false)) {
            return [
                'status' => 'bypass',
                'exists' => is_file($path),
                'valid' => true,
                'sealed' => false,
                'schema' => null,
                'errors' => [],
                'metadata' => null,
            ];
        }

        if (! is_file($path)) {
            return [
                'status' => 'missing',
                'exists' => false,
                'valid' => false,
                'sealed' => false,
                'schema' => null,
                'errors' => [],
                'metadata' => null,
            ];
        }

        if (is_link($path)) {
            return $this->invalidInspection('Installation lock must not be a symbolic link.');
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable $exception) {
            return $this->invalidInspection(
                'Installation lock JSON is unreadable: '.$exception->getMessage(),
            );
        }

        if (! is_array($decoded)) {
            return $this->invalidInspection('Installation lock payload is not a JSON object.');
        }

        $errors = $this->requiredMetadataErrors($decoded);
        $schema = isset($decoded[self::SCHEMA_KEY])
            ? (int) $decoded[self::SCHEMA_KEY]
            : null;

        if ($schema === null) {
            return [
                'status' => $errors === [] ? 'legacy-unsealed' : 'invalid',
                'exists' => true,
                'valid' => $errors === [],
                'sealed' => false,
                'schema' => null,
                'errors' => $errors,
                'metadata' => $errors === [] ? $decoded : null,
            ];
        }

        $expectedSchema = (int) config('installer.lock_schema', 2);
        if ($schema !== $expectedSchema) {
            $errors[] = sprintf(
                'Installation lock schema [%d] is not supported; expected [%d].',
                $schema,
                $expectedSchema,
            );
        }

        $storedHash = strtolower(trim((string) ($decoded[self::HASH_KEY] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $storedHash) !== 1) {
            $errors[] = 'Installation lock integrity digest is missing or invalid.';
        } else {
            $unsigned = $decoded;
            unset($unsigned[self::HASH_KEY]);
            $calculated = $this->fingerprint($unsigned);

            if (! hash_equals($storedHash, $calculated)) {
                $errors[] = 'Installation lock integrity digest does not match its metadata.';
            }
        }

        return [
            'status' => $errors === [] ? 'sealed-valid' : 'invalid',
            'exists' => true,
            'valid' => $errors === [],
            'sealed' => true,
            'schema' => $schema,
            'errors' => $errors,
            'metadata' => $errors === [] ? $this->publicMetadata($decoded) : null,
        ];
    }

    /** @return array<string, mixed>|null */
    public function metadata(): ?array
    {
        if (! $this->isInstalled()) {
            return null;
        }

        $inspection = $this->inspect();
        if (($inspection['valid'] ?? false) !== true) {
            $errors = implode('; ', (array) ($inspection['errors'] ?? []));
            throw new RuntimeException(
                'Nexora installation lock exists but failed integrity validation. '
                .'The installer remains closed. '.$errors,
            );
        }

        return is_array($inspection['metadata'] ?? null)
            ? $inspection['metadata']
            : null;
    }

    /** @param array<string, mixed> $changes */
    public function updateMetadata(array $changes): void
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException('Cannot update installation metadata before Nexora is installed.');
        }

        $current = $this->metadata();
        if (! is_array($current)) {
            throw new RuntimeException('Installed lock metadata is unavailable; metadata update is blocked.');
        }

        // Historical provenance must not be silently rewritten by upgrades.
        unset($changes['installed_at'], $changes['installation_id']);
        $this->writeSealed([...$current, ...$changes]);
    }

    /** @param array<string, mixed> $metadata */
    public function markInstalled(array $metadata): void
    {
        $path = $this->lockPath();
        $directory = dirname($path);

        if (! is_dir($directory)
            && ! mkdir($directory, 0755, true)
            && ! is_dir($directory)) {
            throw new RuntimeException(
                "Unable to create installation lock directory [{$directory}].",
            );
        }

        $this->writeSealed([
            ...$metadata,
            'installed_at' => now()->toIso8601String(),
        ]);
    }

    /** @param array<string, mixed> $metadata */
    private function writeSealed(array $metadata): void
    {
        unset($metadata[self::HASH_KEY], $metadata[self::SCHEMA_KEY]);

        $sealed = [
            ...$metadata,
            self::SCHEMA_KEY => (int) config('installer.lock_schema', 2),
        ];
        $sealed[self::HASH_KEY] = $this->fingerprint($sealed);

        $payload = json_encode(
            $sealed,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        $this->files->write($this->lockPath(), $payload, 0755, 0600);
    }

    /** @param array<string, mixed> $metadata @return list<string> */
    private function requiredMetadataErrors(array $metadata): array
    {
        $errors = [];

        foreach (['installation_id', 'version', 'installed_at'] as $field) {
            if (trim((string) ($metadata[$field] ?? '')) === '') {
                $errors[] = "Installation lock field [{$field}] is missing.";
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $metadata @return array<string, mixed> */
    private function publicMetadata(array $metadata): array
    {
        unset($metadata[self::HASH_KEY], $metadata[self::SCHEMA_KEY]);

        return $metadata;
    }

    /** @return array<string, mixed> */
    private function invalidInspection(string $message): array
    {
        return [
            'status' => 'invalid',
            'exists' => true,
            'valid' => false,
            'sealed' => false,
            'schema' => null,
            'errors' => [$message],
            'metadata' => null,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function fingerprint(array $payload): string
    {
        return hash(
            'sha256',
            json_encode(
                $this->canonicalize($payload),
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
