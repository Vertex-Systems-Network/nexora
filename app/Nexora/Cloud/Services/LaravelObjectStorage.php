<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LaravelObjectStorage implements ObjectStorageContract
{
    public function disk(): string
    {
        $configured = trim((string) config('nexora_cloud.object_storage_disk', ''));
        return $configured !== '' ? $configured : (string) config('filesystems.default', 'local');
    }

    public function capabilities(): array
    {
        $disk = $this->disk();
        $driver = (string) config("filesystems.disks.{$disk}.driver", 'unknown');
        return [
            'disk' => $disk,
            'driver' => $driver,
            'shared' => in_array($driver, (array) config('nexora-storage-runtime.shared_drivers', ['s3']), true),
            'temporary_urls' => in_array($driver, ['s3'], true),
            'public_urls' => in_array($driver, ['s3', 'local'], true),
        ];
    }

    public function put(string $path, string $contents, array $options = []): void
    {
        if (! Storage::disk($this->disk())->put($path, $contents, $options)) throw new RuntimeException('Object storage write failed.');
    }

    public function get(string $path): string
    {
        $value = Storage::disk($this->disk())->get($path);
        if (! is_string($value)) throw new RuntimeException('Object storage read failed.');
        return $value;
    }

    public function exists(string $path): bool { return Storage::disk($this->disk())->exists($path); }
    public function delete(string $path): void { Storage::disk($this->disk())->delete($path); }

    public function url(string $path): ?string
    {
        try { return Storage::disk($this->disk())->url($path); } catch (\Throwable) { return null; }
    }
}
