<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use ZipArchive;

final class ArchiveInspector
{
    /** @return array{files:array<string,string>,manifest:?string,findings:list<SecurityFinding>,metrics:array<string,mixed>} */
    public function inspect(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Nexora Sentinel requires the PHP zip extension (ext-zip) to scan ZIP packages safely.');
        }

        $zip = new ZipArchive();
        $result = $zip->open($path, ZipArchive::RDONLY);
        if ($result !== true) {
            throw new \RuntimeException("Unable to open package archive. ZipArchive error code: {$result}");
        }

        $findings = [];
        $files = [];
        $manifest = null;
        $names = [];
        $entryCount = $zip->numFiles;
        $totalUncompressed = 0;
        $totalCompressed = 0;
        $limits = (array) config('sentinel.archive', []);
        $maxEntries = (int) ($limits['max_entries'] ?? 5000);
        $maxTotal = (int) ($limits['max_total_uncompressed_bytes'] ?? 262_144_000);
        $maxEntry = (int) ($limits['max_entry_uncompressed_bytes'] ?? 52_428_800);
        $maxSource = (int) ($limits['max_source_scan_bytes'] ?? 2_097_152);
        $maxRatio = (int) ($limits['max_compression_ratio'] ?? 200);

        if ($entryCount > $maxEntries) {
            $findings[] = new SecurityFinding('NEX-ARC-0001', FindingSeverity::Critical, 'archive', 'Archive contains too many entries', "Archive has {$entryCount} entries; the configured limit is {$maxEntries}.", hardBlock: true);
        }

        $entriesToInspect = min($entryCount, $maxEntries);
        for ($index = 0; $index < $entriesToInspect; $index++) {
            $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);
            if (! is_array($stat) || ! isset($stat['name'])) {
                $findings[] = new SecurityFinding('NEX-ARC-0002', FindingSeverity::High, 'archive', 'Unreadable archive entry', "Sentinel could not inspect ZIP entry #{$index}.", hardBlock: true);
                continue;
            }

            $rawName = (string) $stat['name'];
            $name = str_replace('\\', '/', $rawName);
            $size = (int) ($stat['size'] ?? 0);
            $compressed = (int) ($stat['comp_size'] ?? 0);
            $totalUncompressed += max(0, $size);
            $totalCompressed += max(0, $compressed);

            $this->inspectPath($name, $findings);

            $normalizedKey = strtolower(rtrim(preg_replace('#/+#', '/', $name) ?? $name, '/'));
            if ($normalizedKey !== '') {
                if (isset($names[$normalizedKey])) {
                    $findings[] = new SecurityFinding('NEX-ARC-0003', FindingSeverity::High, 'archive', 'Duplicate normalized archive path', "Multiple ZIP entries normalize to [{$name}], which can be used to confuse extraction or overwrite checks.", $name, hardBlock: true);
                }
                $names[$normalizedKey] = true;
            }

            if ($size > $maxEntry) {
                $findings[] = new SecurityFinding('NEX-ARC-0004', FindingSeverity::High, 'archive', 'Oversized archive entry', "Entry size {$size} bytes exceeds the configured {$maxEntry} byte limit.", $name, hardBlock: true);
            }

            if ($compressed > 0 && $size > 1_048_576 && ($size / $compressed) > $maxRatio) {
                $ratio = round($size / $compressed, 1);
                $findings[] = new SecurityFinding('NEX-ARC-0005', FindingSeverity::Critical, 'archive', 'Suspicious compression ratio', "Entry expands at approximately {$ratio}:1, exceeding the configured {$maxRatio}:1 ZIP-bomb threshold.", $name, hardBlock: true);
            }

            if ($this->isSymlink($zip, $index)) {
                $findings[] = new SecurityFinding('NEX-ARC-0006', FindingSeverity::Critical, 'archive', 'Symbolic link entry is not allowed', 'Package archives may not contain symbolic links because they can escape the package boundary during extraction.', $name, hardBlock: true);
            }

            if (method_exists($zip, 'getEncryptionName')) {
                $encryption = $zip->getEncryptionName($index);
                if (is_string($encryption) && $encryption !== '' && strtolower($encryption) !== 'none') {
                    $findings[] = new SecurityFinding('NEX-ARC-0007', FindingSeverity::Critical, 'archive', 'Encrypted archive entry cannot be inspected', "Entry uses encryption [{$encryption}]. Sentinel never activates content it cannot fully inspect.", $name, hardBlock: true);
                }
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            $this->inspectFileName($name, $findings);

            if (! $this->shouldAnalyzeContents($name)) {
                continue;
            }

            if ($size > $maxSource) {
                $findings[] = new SecurityFinding('NEX-ARC-0008', FindingSeverity::High, 'archive', 'Source/config file is too large to inspect safely', "Inspectable source file is {$size} bytes; Sentinel's per-source limit is {$maxSource} bytes. Unscanned source is never activated.", $name, hardBlock: true);
                continue;
            }

            $contents = $zip->getFromIndex($index, $maxSource + 1);
            if ($contents === false || strlen($contents) > $maxSource) {
                $findings[] = new SecurityFinding('NEX-ARC-0009', FindingSeverity::High, 'archive', 'Archive source entry could not be fully read', 'Sentinel requires complete readable source/config content before activation.', $name, hardBlock: true);
                continue;
            }

            if ($name === 'nexora.json') {
                $manifest = $contents;
            }

            $files[$name] = $contents;
        }

        if ($totalUncompressed > $maxTotal) {
            $findings[] = new SecurityFinding('NEX-ARC-0013', FindingSeverity::Critical, 'archive', 'Archive expands beyond the package size limit', "Total uncompressed size {$totalUncompressed} bytes exceeds {$maxTotal} bytes.", hardBlock: true);
        }

        $zip->close();

        return [
            'files' => $files,
            'manifest' => $manifest,
            'findings' => $findings,
            'metrics' => [
                'entries' => $entryCount,
                'total_uncompressed_bytes' => $totalUncompressed,
                'total_compressed_bytes' => $totalCompressed,
                'analyzed_files' => count($files),
            ],
        ];
    }

    /** @param list<SecurityFinding> $findings */
    private function inspectPath(string $name, array &$findings): void
    {
        if (str_contains($name, "\0")) {
            $findings[] = new SecurityFinding('NEX-ARC-0010', FindingSeverity::Critical, 'archive', 'Null byte in archive path', 'Null bytes can bypass path validation in vulnerable extractors.', $name, hardBlock: true);
        }

        if (str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name) === 1) {
            $findings[] = new SecurityFinding('NEX-ARC-0011', FindingSeverity::Critical, 'archive', 'Absolute archive path detected', 'Package entries must remain relative to the package root.', $name, hardBlock: true);
        }

        $segments = explode('/', $name);
        if (in_array('..', $segments, true)) {
            $findings[] = new SecurityFinding('NEX-ARC-0012', FindingSeverity::Critical, 'archive', 'Path traversal detected', 'The entry contains a parent-directory segment and could escape the intended extraction directory.', $name, hardBlock: true);
        }

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            if ($segment !== trim($segment) || str_ends_with($segment, '.') || str_contains($segment, ':')) {
                $findings[] = new SecurityFinding('NEX-ARC-0014', FindingSeverity::High, 'archive', 'Ambiguous platform-specific path segment', 'Trailing spaces/dots or colon-based stream/ADS syntax can resolve differently across filesystems.', $name, hardBlock: true);
                break;
            }
            $base = strtoupper(pathinfo($segment, PATHINFO_FILENAME));
            if (preg_match('/^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/', $base) === 1) {
                $findings[] = new SecurityFinding('NEX-ARC-0015', FindingSeverity::High, 'archive', 'Windows reserved device filename detected', 'Reserved device names are not valid portable package paths and can cause extraction ambiguity.', $name, hardBlock: true);
                break;
            }
            if (preg_match('/[\x00-\x1F\x7F]/', $segment) === 1) {
                $findings[] = new SecurityFinding('NEX-ARC-0016', FindingSeverity::High, 'archive', 'Control character in archive path', 'Control characters can hide or confuse package paths during review and extraction.', $name, hardBlock: true);
                break;
            }
        }
    }

    /** @param list<SecurityFinding> $findings */
    private function inspectFileName(string $name, array &$findings): void
    {
        $lower = strtolower($name);
        $extension = strtolower(pathinfo($lower, PATHINFO_EXTENSION));
        $nestedArchives = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'];
        $binaries = ['exe', 'dll', 'com', 'scr', 'msi', 'dylib', 'so'];
        $scripts = ['bat', 'cmd', 'ps1', 'vbs', 'wsf'];
        $secrets = ['.env', '.env.local', '.env.production', 'id_rsa', 'id_ed25519'];

        if (in_array($extension, $nestedArchives, true)) {
            $findings[] = new SecurityFinding('NEX-ARC-0020', FindingSeverity::High, 'archive', 'Nested archive is blocked', 'Nested archives create an unscanned payload boundary. Repackage the extension with inspectable files only.', $name, hardBlock: true);
        }

        if (in_array($extension, $binaries, true)) {
            $findings[] = new SecurityFinding('NEX-ARC-0021', FindingSeverity::Critical, 'archive', 'Native executable payload detected', 'Native executable files require a future isolated-runtime package type and are not allowed in standard Nexora packages.', $name, hardBlock: true);
        }

        if (in_array($extension, $scripts, true)) {
            $findings[] = new SecurityFinding('NEX-ARC-0022', FindingSeverity::High, 'archive', 'Operating-system script detected', 'Standard packages may not ship executable OS scripts.', $name, hardBlock: true);
        }

        if ($extension === 'phar') {
            $findings[] = new SecurityFinding('NEX-ARC-0023', FindingSeverity::Critical, 'archive', 'PHAR payload detected', 'Executable PHAR archives are not accepted inside standard packages.', $name, hardBlock: true);
        }

        if (preg_match('/\.(?:jpe?g|png|gif|svg|webp|txt|pdf)\.(?:php\d*|phtml|phar)$/i', $lower) === 1) {
            $findings[] = new SecurityFinding('NEX-ARC-0024', FindingSeverity::Critical, 'archive', 'Deceptive double extension detected', 'The filename disguises executable PHP as a document or media asset.', $name, hardBlock: true);
        }

        if (in_array(basename($lower), $secrets, true) || in_array($extension, ['pem', 'key', 'p12', 'pfx'], true)) {
            $findings[] = new SecurityFinding('NEX-ARC-0025', FindingSeverity::Critical, 'secrets', 'Potential secret or private key included', 'Packages must never distribute environment files, private keys or credential containers.', $name, hardBlock: true);
        }

        if (basename($lower) === '.htaccess' || basename($lower) === 'web.config') {
            $findings[] = new SecurityFinding('NEX-ARC-0026', FindingSeverity::High, 'webserver', 'Web-server configuration override detected', 'Extensions may not alter server routing or execution policy through package-local web-server configuration.', $name, hardBlock: true);
        }

        if (preg_match('#(?:^|/)public/.*\.(?:php\d*|phtml)$#i', $name) === 1) {
            $findings[] = new SecurityFinding('NEX-ARC-0027', FindingSeverity::High, 'execution', 'Directly executable PHP under public path', 'Public PHP entry points bypass Nexora routing, authorization and capability enforcement.', $name, hardBlock: true);
        }
    }

    private function shouldAnalyzeContents(string $name): bool
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($extension, ['php', 'phtml', 'inc', 'json', 'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx', 'css', 'svg', 'html', 'htm'], true)
            || in_array(basename(strtolower($name)), ['composer.json', 'nexora.json'], true);
    }

    private function isSymlink(ZipArchive $zip, int $index): bool
    {
        if (! method_exists($zip, 'getExternalAttributesIndex')) {
            return false;
        }

        $operationsSystem = 0;
        $attributes = 0;
        if (! $zip->getExternalAttributesIndex($index, $operationsSystem, $attributes)) {
            return false;
        }

        if (defined(ZipArchive::class.'::OPSYS_UNIX') && $operationsSystem !== ZipArchive::OPSYS_UNIX) {
            return false;
        }

        $mode = ($attributes >> 16) & 0xF000;

        return $mode === 0xA000;
    }
}
