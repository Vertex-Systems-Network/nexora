<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use RuntimeException;

final class SentinelApprovalGuard
{
    public function assertCurrent(QuarantinePackage $package, SecurityScan $scan): void
    {
        if ((string) $scan->quarantine_package_id !== (string) $package->id
            || $scan->status !== 'completed'
            || $scan->decision !== 'allow') {
            throw new RuntimeException('Only a completed Sentinel ALLOW decision for this package can be promoted.');
        }

        if (! in_array((string) $package->status, ['scanned', 'installed'], true)) {
            throw new RuntimeException('The package is not in a promotable Sentinel state. Rescan it before installation.');
        }

        $latestScanId = $package->scans()->latest('created_at')->value('id');
        if (! is_string($latestScanId) || ! hash_equals($latestScanId, (string) $scan->id)) {
            throw new RuntimeException('A newer Sentinel scan exists. Only the latest completed ALLOW decision may be promoted.');
        }

        $path = (string) $package->path;
        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('The scanned quarantine archive is no longer available.');
        }

        $currentSha256 = hash_file('sha256', $path);
        if (! is_string($currentSha256)
            || ! hash_equals((string) $package->sha256, $currentSha256)
            || ! hash_equals((string) $scan->source_sha256, $currentSha256)) {
            throw new RuntimeException('The package changed after Sentinel approval. Re-upload and rescan it.');
        }
    }
}
