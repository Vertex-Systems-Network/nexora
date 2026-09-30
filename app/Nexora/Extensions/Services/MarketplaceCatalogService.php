<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\MarketplaceCatalogItem;
use App\Models\MarketplaceSource;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use App\Nexora\Foundation\Transfers\TransferSafety;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final readonly class MarketplaceCatalogService
{
    private const MAX_PACKAGES = 5000;
    private const MAX_METADATA_BYTES = 65536;

    public function __construct(
        private WebhookUrlPolicy $urls,
        private ApprovedHttpClient $http,
        private TransferSafety $transfers,
    ) {
    }

    public function sync(MarketplaceSource $source): int
    {
        if (! $source->isActive()) {
            throw new RuntimeException('Marketplace source is paused. Resume it before synchronizing the catalog.');
        }

        $url = rtrim((string) $source->base_url, '/').'/nexora-marketplace.json';
        $this->urls->assertAllowed($url, true);
        $maximum = max(1024, (int) config('nexora-transfers.marketplace.max_catalog_bytes', 8_388_608));
        $this->transfers->assertLocalCapacity($this->transfers->temporaryRoot(), min($maximum, 1_048_576));
        $temp = $this->transfers->temporaryPath('marketplace-catalog', '.json');

        try {
            $response = $this->http->external($url)->acceptJson()->timeout(12)->withOptions([
                'sink' => $temp,
                'progress' => static function ($downloadTotal, $downloadedBytes) use ($maximum): void {
                    if ((is_numeric($downloadTotal) && (float) $downloadTotal > $maximum)
                        || (is_numeric($downloadedBytes) && (float) $downloadedBytes > $maximum)) {
                        throw new RuntimeException('Marketplace catalog exceeds the configured download limit.');
                    }
                },
            ])->get($url);
            if (! $response->successful()) {
                throw new RuntimeException('Marketplace catalog returned HTTP '.$response->status().'.');
            }

            $length = trim((string) $response->header('Content-Length'));
            if ($length !== '' && ctype_digit($length) && (int) $length > $maximum) {
                throw new RuntimeException('Marketplace catalog Content-Length exceeds the configured download limit.');
            }
            $size = $this->transfers->assertSourceFile($temp, $maximum, 'Marketplace catalog');
            if ($length !== '' && ctype_digit($length) && (int) $length !== $size) {
                throw new RuntimeException('Marketplace catalog Content-Length does not match the downloaded bytes.');
            }

            $raw = file_get_contents($temp);
            if (! is_string($raw)) {
                throw new RuntimeException('Marketplace catalog could not be read after bounded download.');
            }
            try {
                $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new RuntimeException('Marketplace catalog JSON is invalid.');
            }
        } finally {
            if (is_file($temp)) @unlink($temp);
        }

        if (! is_array($payload) || ! is_array($payload['packages'] ?? null)) {
            throw new RuntimeException('Marketplace catalog format is invalid.');
        }
        if (isset($payload['schema']) && (! is_int($payload['schema']) || $payload['schema'] !== 1)) {
            throw new RuntimeException('Marketplace catalog schema is unsupported. Expected schema 1.');
        }
        if (count($payload['packages']) > self::MAX_PACKAGES) {
            throw new RuntimeException('Marketplace catalog exceeds the 5000-package synchronization limit.');
        }

        $normalized = [];
        foreach ($payload['packages'] as $index => $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Marketplace catalog package #'.($index + 1).' must be an object.');
            }
            $entry = $this->normalizeItem($item, $source, $index);
            if (isset($normalized[$entry['package_identifier']])) {
                throw new RuntimeException('Marketplace catalog contains duplicate package identifier: '.$entry['package_identifier'].'.');
            }
            $normalized[$entry['package_identifier']] = $entry;
        }

        $generation = (string) Str::uuid();
        DB::transaction(function () use ($source, $normalized, $generation): void {
            foreach ($normalized as $identifier => $entry) {
                $catalogItem = MarketplaceCatalogItem::query()->firstOrNew([
                    'source_id' => $source->id,
                    'package_identifier' => $identifier,
                ]);
                if (! $catalogItem->exists) {
                    $catalogItem->id = (string) Str::uuid();
                }
                $catalogItem->fill($entry + [
                    'sync_generation' => $generation,
                    'synced_at' => now(),
                ]);
                $catalogItem->save();
            }

            $query = MarketplaceCatalogItem::query()->where('source_id', $source->id);
            if ($normalized === []) {
                $query->delete();
            } else {
                $query->whereNotIn('package_identifier', array_keys($normalized))->delete();
            }

            $source->forceFill([
                'catalog_generation' => $generation,
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();
        });

        return count($normalized);
    }

    /** @param array<string,mixed> $item @return array<string,mixed> */
    private function normalizeItem(array $item, MarketplaceSource $source, int $index): array
    {
        foreach (['id', 'name', 'type', 'version', 'artifact_url'] as $field) {
            if (! is_string($item[$field] ?? null) || trim((string) $item[$field]) === '') {
                throw new RuntimeException('Marketplace catalog package #'.($index + 1).' is missing required field '.$field.'.');
            }
        }

        $identifier = trim((string) $item['id']);
        if (mb_strlen($identifier) > 180 || preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,179}$/', $identifier) !== 1) {
            throw new RuntimeException('Marketplace package identifier is invalid: '.$identifier.'.');
        }

        $name = trim((string) $item['name']);
        if (mb_strlen($name) > 180) {
            throw new RuntimeException('Marketplace package name exceeds 180 characters for '.$identifier.'.');
        }

        $type = trim((string) $item['type']);
        if (! in_array($type, ['extension', 'app', 'integration', 'studio-pack', 'theme'], true)) {
            throw new RuntimeException('Marketplace package '.$identifier.' has unsupported type '.$type.'.');
        }

        $version = trim((string) $item['version']);
        if (mb_strlen($version) > 64) {
            throw new RuntimeException('Marketplace package version exceeds 64 characters for '.$identifier.'.');
        }

        $artifactUrl = trim((string) $item['artifact_url']);
        if (mb_strlen($artifactUrl) > 1000) {
            throw new RuntimeException('Marketplace artifact URL exceeds 1000 characters for '.$identifier.'.');
        }
        $this->urls->assertAllowed($artifactUrl, false);

        $publisherKey = isset($item['publisher_key_id']) ? trim((string) $item['publisher_key_id']) : null;
        if ($publisherKey === '') $publisherKey = null;
        if ($publisherKey !== null && mb_strlen($publisherKey) > 160) {
            throw new RuntimeException('Marketplace publisher key identity exceeds 160 characters for '.$identifier.'.');
        }
        if ($source->trusted_publishers_only && $publisherKey === null) {
            throw new RuntimeException('Trusted-only marketplace package '.$identifier.' is missing publisher_key_id.');
        }

        $sha = isset($item['sha256']) ? strtolower(trim((string) $item['sha256'])) : null;
        if ($sha === '') $sha = null;
        if ($sha !== null && preg_match('/^[a-f0-9]{64}$/', $sha) !== 1) {
            throw new RuntimeException('Marketplace package '.$identifier.' has an invalid SHA-256 value.');
        }

        $description = isset($item['description']) ? trim((string) $item['description']) : '';
        if (mb_strlen($description) > 20000) {
            throw new RuntimeException('Marketplace package description exceeds the allowed size for '.$identifier.'.');
        }

        $metadata = $item['metadata'] ?? [];
        if (! is_array($metadata)) {
            throw new RuntimeException('Marketplace package metadata must be an object or array for '.$identifier.'.');
        }
        $encoded = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($encoded) > self::MAX_METADATA_BYTES) {
            throw new RuntimeException('Marketplace package metadata exceeds 64 KiB for '.$identifier.'.');
        }

        return [
            'package_identifier' => $identifier,
            'name' => $name,
            'type' => $type,
            'latest_version' => $version,
            'description' => $description,
            'publisher_key_id' => $publisherKey,
            'artifact_url' => $artifactUrl,
            'artifact_sha256' => $sha,
            'metadata' => $metadata,
        ];
    }
}
