<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

use ZipArchive;

final class PackageJsonReader
{
    /** @return array<string,mixed>|null */
    public function read(string $zipPath, string $entry, int $maxBytes = 4_194_304): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) return null;
        try {
            $stat = $zip->statName($entry);
            if (! is_array($stat) || (int) ($stat['size'] ?? 0) > $maxBytes) return null;
            $raw = $zip->getFromName($entry);
            if (! is_string($raw) || $raw === '') return null;
            $decoded = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        } finally {
            $zip->close();
        }
    }
}
