<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use Illuminate\Support\Str;
use RuntimeException;

final class NodeIdentity
{
    private ?string $resolved = null;

    public function __construct(private readonly AtomicFileWriter $files) {}

    public function key(): string
    {
        if ($this->resolved !== null) return $this->resolved;

        $configured = trim((string) config('nexora_cloud.node_id', ''));
        if ($configured !== '') return $this->resolved = $this->normalize($configured);

        $path = storage_path('app/nexora/runtime/node-id');
        $directory = dirname($path);
        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Nexora could not prepare protected node identity storage.');
        }

        if (is_file($path)) {
            $existing = trim((string) @file_get_contents($path));
            if ($existing !== '') return $this->resolved = $this->normalize($existing);
        }

        $generated = 'node-'.Str::lower(Str::random(20));
        try {
            $this->files->write($path, $generated, 0700, 0600);
        } catch (RuntimeException $exception) {
            throw new RuntimeException('Nexora could not persist the generated node identity. Set NEXORA_NODE_ID explicitly.', 0, $exception);
        }

        return $this->resolved = $generated;
    }

    public function hostname(): string
    {
        $host = gethostname();
        return is_string($host) && $host !== '' ? $host : 'unknown';
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?: '';
        if ($value === '' || strlen($value) > 128) throw new RuntimeException('NEXORA_NODE_ID must resolve to 1-128 safe characters.');
        return $value;
    }
}
