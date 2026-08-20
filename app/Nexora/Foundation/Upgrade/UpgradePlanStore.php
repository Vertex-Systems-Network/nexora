<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

final class UpgradePlanStore
{
    public function __construct(private readonly AtomicFileWriter $files) {}
    public function path(): string
    {
        return (string) config('nexora-upgrade.plan_path', base_path('storage/app/nexora/upgrade/active-plan.json'));
    }

    /** @return array<string,mixed>|null */
    public function read(): ?array
    {
        $path = $this->path();
        if (! is_file($path)) return null;
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) return null;
        $expected = trim((string) ($decoded['plan_sha256'] ?? ''));
        $actual = hash('sha256', json_encode($this->canonical($decoded), JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if ($expected === '' || ! hash_equals($expected, $actual)) {
            throw new \RuntimeException('Active Nexora upgrade plan integrity verification failed. Create a new plan.');
        }
        return $decoded;
    }

    /** @param array<string,mixed> $payload */
    public function write(array $payload): array
    {
        $path = $this->path();
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Unable to create Nexora upgrade state directory [{$directory}].");
        }
        $payload['schema'] = 1;
        $payload['written_at'] = now()->toIso8601String();
        $payload['plan_sha256'] = hash('sha256', json_encode($this->canonical($payload), JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $json = json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
        $this->files->write($path, $json, 0755, 0600);
        return $payload;
    }

    /** @param array<string,mixed> $plan */
    public function archive(array $plan, string $status): string
    {
        $history = (string) config('nexora-upgrade.history_path', base_path('storage/app/nexora/upgrade/history'));
        if (! is_dir($history) && ! mkdir($history, 0755, true) && ! is_dir($history)) throw new \RuntimeException('Unable to create upgrade history directory.');
        $id = preg_replace('/[^0-9A-Za-z._-]/', '-', (string) ($plan['upgrade_id'] ?? now()->format('YmdHis'))) ?: now()->format('YmdHis');
        $target = $history.'/'.$id.'-'.$status.'.json';
        $plan['final_status'] = $status;
        $plan['archived_at'] = now()->toIso8601String();
        $this->files->write($target, json_encode($plan, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n", 0755, 0600);
        return $target;
    }

    public function clear(): void
    {
        $path = $this->path();
        if (is_file($path) && ! @unlink($path)) throw new \RuntimeException('Unable to clear the active Nexora upgrade plan.');
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonical(array $payload): array
    {
        unset($payload['plan_sha256']);
        ksort($payload);
        return $payload;
    }
}
