<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\MarketplaceCatalogItem;
use App\Models\QuarantinePackage;
use App\Models\SupplyChainArtifact;
use App\Models\TrustedPublisher;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use App\Nexora\Foundation\Transfers\TransferSafety;
use App\Nexora\Security\Sentinel\Support\QuarantineManager;
use App\Nexora\Security\Sentinel\Support\ScanRecorder;
use RuntimeException;

final readonly class MarketplacePackageStager
{
    public function __construct(
        private WebhookUrlPolicy $urls,
        private QuarantineManager $quarantine,
        private ScanRecorder $scanner,
        private TransferSafety $transfers,
        private ApprovedHttpClient $http,
    ) {
    }

    public function stage(MarketplaceCatalogItem $item, ?int $userId): QuarantinePackage
    {
        $item->loadMissing('source');
        if (! $item->source) {
            throw new RuntimeException('Marketplace source is no longer available.');
        }
        if (! $item->source->isActive()) {
            throw new RuntimeException('Marketplace source is paused. Resume and synchronize it before staging packages.');
        }
        if ($item->source->last_synced_at === null || $item->synced_at === null || $item->synced_at->lt($item->source->last_synced_at->copy()->subSecond())) {
            throw new RuntimeException('Marketplace package metadata is stale. Synchronize the source before staging this package.');
        }

        if ($item->source->trusted_publishers_only) {
            $keyId = trim((string) $item->publisher_key_id);
            if ($keyId === '') {
                throw new RuntimeException('This marketplace only accepts trusted publishers, but the catalog item has no publisher key identity.');
            }
            $trusted = TrustedPublisher::query()->where('key_id', $keyId)->where('status', 'active')->exists();
            if (! $trusted) {
                throw new RuntimeException('This package publisher is not trusted by this Nexora installation. Add or activate the publisher verification key before staging it.');
            }
        }

        $this->urls->assertAllowed((string) $item->artifact_url, true);
        $maximum = max(1024, (int) config('nexora-transfers.marketplace.max_download_bytes', 52_428_800));
        $this->transfers->assertLocalCapacity($this->transfers->temporaryRoot(), min($maximum, 8_388_608));
        $temp = $this->transfers->temporaryPath('marketplace', '.zip');

        try {
            $response = $this->http->external((string) $item->artifact_url)->timeout(30)->withOptions([
                'sink' => $temp,
                'progress' => static function ($downloadTotal, $downloadedBytes) use ($maximum): void {
                    if ((is_numeric($downloadTotal) && (float) $downloadTotal > $maximum) || (is_numeric($downloadedBytes) && (float) $downloadedBytes > $maximum)) {
                        throw new RuntimeException('Marketplace package exceeds the configured download limit.');
                    }
                },
            ])->get((string) $item->artifact_url);
            if (! $response->successful()) {
                throw new RuntimeException('Marketplace package download returned HTTP '.$response->status().'.');
            }

            $length = trim((string) $response->header('Content-Length'));
            if ($length !== '' && ctype_digit($length) && (int) $length > $maximum) {
                throw new RuntimeException('Marketplace package Content-Length exceeds the configured download limit.');
            }
            $size = $this->transfers->assertSourceFile($temp, $maximum, 'Marketplace package');
            if ($length !== '' && ctype_digit($length) && (int) $length !== $size) {
                throw new RuntimeException('Marketplace package Content-Length does not match the downloaded bytes.');
            }

            $sha = hash_file('sha256', $temp);
            if (! is_string($sha)) {
                throw new RuntimeException('Unable to hash downloaded marketplace package.');
            }
            if ($item->artifact_sha256 && ! hash_equals(strtolower((string) $item->artifact_sha256), strtolower($sha))) {
                throw new RuntimeException('Marketplace package SHA-256 does not match the catalog metadata.');
            }

            $package = $this->quarantine->storeLocalFile(
                $temp,
                $item->package_identifier.'-'.$item->latest_version.'.zip',
                'application/zip',
                $userId,
                true,
            );
            $scan = $this->scanner->scan($package, $userId);

            if ($item->source->trusted_publishers_only) {
                $artifact = SupplyChainArtifact::query()
                    ->with('publisher:id,key_id,status')
                    ->where('scan_id', $scan->id)
                    ->first();
                if (! $artifact || $artifact->signature_status !== 'verified' || ! $artifact->publisher || $artifact->publisher->status !== 'active' || ! hash_equals((string) $item->publisher_key_id, (string) $artifact->publisher->key_id)) {
                    throw new RuntimeException('Marketplace trusted-publisher verification failed after download. The package remains in quarantine and cannot be installed from this trusted-only catalog entry.');
                }
            }

            return $package;
        } finally {
            if (is_file($temp)) @unlink($temp);
        }
    }
}
