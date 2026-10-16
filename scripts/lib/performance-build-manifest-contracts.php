<?php

declare(strict_types=1);

/**
 * Validate production Vite manifest references before the performance verifier
 * reads any build asset. Both the manifest and all referenced assets must live
 * in a real, non-symlinked public/build tree.
 *
 * @param array<mixed> $manifest
 * @return list<string>
 */
function nexoraPerformanceBuildManifestErrors(string $root, array $manifest): array
{
    $build = $root.'/public/build';
    if (!is_dir($build) || is_link($build) || is_link($root.'/public')
        || is_link($root.'/public/hot') || is_file($root.'/public/hot')) {
        return ['Unsafe or development-mode production build directory.'];
    }
    if (array_is_list($manifest) || count($manifest) < 2 || count($manifest) > 10000
        || !isset($manifest['resources/js/app.tsx'], $manifest['resources/css/app.css'])) {
        return ['Vite manifest entries are missing or malformed.'];
    }

    foreach ($manifest as $key => $entry) {
        if (!is_string($key) || !is_array($entry)
            || !is_string($entry['file'] ?? null)) {
            return ['Invalid Vite manifest entry.'];
        }
        $assets = [$entry['file']];
        foreach (['css', 'assets'] as $field) {
            if (!array_key_exists($field, $entry)) continue;
            if (!is_array($entry[$field]) || !array_is_list($entry[$field])) {
                return ['Invalid Vite manifest asset list.'];
            }
            foreach ($entry[$field] as $asset) {
                if (!is_string($asset)) return ['Invalid Vite manifest asset.'];
                $assets[] = $asset;
            }
        }
        foreach ($assets as $asset) {
            if (!nexoraPerformanceBuildSafeManifestAsset($build, $asset)) {
                return ['Unsafe, missing or unreadable Vite manifest asset.'];
            }
        }
        foreach (['imports', 'dynamicImports'] as $field) {
            if (!array_key_exists($field, $entry)) continue;
            if (!is_array($entry[$field]) || !array_is_list($entry[$field])) {
                return ['Invalid Vite manifest import list.'];
            }
            foreach ($entry[$field] as $import) {
                if (!is_string($import) || !array_key_exists($import, $manifest)) {
                    return ['Missing or malformed Vite manifest import reference.'];
                }
            }
        }
    }

    // The producer scans ALL generated assets, not just manifest references.
    // Deny symlinks anywhere in the tree (including unused assets), special
    // files, and unreasonable counts/sizes before any file_get_contents().
    $files = 0;
    $size = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($build, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $node) {
            if ($node->isLink()) return ['Symlink in generated Vite build tree.'];
            if ($node->isDir()) continue;
            if (!$node->isFile() || !$node->isReadable()) {
                return ['Unsafe generated Vite build file.'];
            }
            $bytes = $node->getSize();
            if (!is_int($bytes) || $bytes < 0 || $bytes > 64 * 1024 * 1024
                || $size > 64 * 1024 * 1024 - $bytes || ++$files > 10000) {
                return ['Generated Vite build exceeds safe scan limits.'];
            }
            $size += $bytes;
        }
    } catch (Throwable) {
        return ['Generated Vite build is unreadable.'];
    }
    return [];
}

function nexoraPerformanceBuildSafeManifestAsset(string $build, string $asset): bool
{
    if ($asset === '' || strlen($asset) > 512 || str_contains($asset, '\\')
        || str_contains($asset, "\0") || str_starts_with($asset, '/')
        || preg_match('/[\x00-\x1f\x7f]/', $asset) === 1) {
        return false;
    }
    $parts = explode('/', $asset);
    $path = $build;
    foreach ($parts as $part) {
        if ($part === '' || $part === '.' || $part === '..') return false;
        $path .= '/'.$part;
        if (is_link($path)) return false;
    }
    return is_file($path) && is_readable($path);
}
