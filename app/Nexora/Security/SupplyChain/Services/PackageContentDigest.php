<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

use RuntimeException;
use ZipArchive;

final class PackageContentDigest
{
    public function calculate(string $zipPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) throw new RuntimeException('Unable to open package while calculating the signed content digest.');
        try {
            $entries = [];
            $maxEntries = (int) config('sentinel.archive.max_entries', 5000);
            $maxTotal = (int) config('sentinel.archive.max_total_uncompressed_bytes', 262_144_000);
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if ($i >= $maxEntries) throw new RuntimeException('Package exceeds the Sentinel entry limit during supply-chain digesting.');
                $stat = $zip->statIndex($i);
                if (! is_array($stat)) continue;
                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
                if ($name === '' || str_ends_with($name, '/')) continue;
                if ($name === 'nexora.signature.json') continue;
                if ($name[0] === '/' || preg_match('~(^|/)\.\.(/|$)~', $name)) throw new RuntimeException('Unsafe archive path encountered during supply-chain digesting.');
                $size = (int) ($stat['size'] ?? 0);
                $total += max(0, $size);
                if ($total > $maxTotal) throw new RuntimeException('Package exceeds the Sentinel uncompressed-byte limit during supply-chain digesting.');
                $entries[] = ['index'=>$i,'name'=>$name,'size'=>$size];
            }
            usort($entries, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
            $root = hash_init('sha256');
            foreach ($entries as $entry) {
                $stream = $zip->getStream($entry['name']);
                if (! is_resource($stream)) throw new RuntimeException('Unable to read package entry for supply-chain digest: '.$entry['name']);
                $fileHash = hash_init('sha256');
                hash_update_stream($fileHash, $stream);
                fclose($stream);
                $digest = hash_final($fileHash);
                hash_update($root, $entry['name']."\0".$entry['size']."\0".$digest."\n");
            }
            return hash_final($root);
        } finally {
            $zip->close();
        }
    }
}
