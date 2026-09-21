<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Filesystem;

use InvalidArgumentException;

final class PortablePath
{
    /** @var list<string> */
    private const WINDOWS_RESERVED = [
        'con','prn','aux','nul',
        'com1','com2','com3','com4','com5','com6','com7','com8','com9',
        'lpt1','lpt2','lpt3','lpt4','lpt5','lpt6','lpt7','lpt8','lpt9',
    ];

    public static function normalizeRelative(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException('Filesystem paths may not contain NUL bytes.');
        }

        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '' || str_starts_with($normalized, '/') || str_starts_with($normalized, '//') || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            throw new InvalidArgumentException('Expected a non-empty relative filesystem path.');
        }

        $parts = explode('/', $normalized);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new InvalidArgumentException('Filesystem path traversal or empty path segments are not allowed.');
            }
            if (str_ends_with($part, '.') || str_ends_with($part, ' ')) {
                throw new InvalidArgumentException('Filesystem path components may not end with a dot or space.');
            }
            $stem = strtolower(explode('.', $part, 2)[0]);
            if (in_array($stem, self::WINDOWS_RESERVED, true)) {
                throw new InvalidArgumentException("Filesystem path component [{$part}] is reserved on Windows.");
            }
            if (str_contains($part, ':')) {
                throw new InvalidArgumentException('Filesystem path components may not contain a colon.');
            }
        }

        return implode('/', $parts);
    }

    public static function join(string $root, string $relative): string
    {
        $normalized = self::normalizeRelative($relative);
        return rtrim($root, '\\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }

    public static function isLexicallyWithin(string $root, string $path): bool
    {
        $root = self::canonicalSeparators(rtrim($root, '\\/'));
        $path = self::canonicalSeparators($path);
        if (PHP_OS_FAMILY === 'Windows') {
            $root = strtolower($root);
            $path = strtolower($path);
        }
        return $path === $root || str_starts_with($path, $root.'/');
    }

    public static function assertNoExistingSymlinkTraversal(string $root, string $path): void
    {
        if (! self::isLexicallyWithin($root, $path)) {
            throw new InvalidArgumentException('Filesystem destination escapes its allowed root.');
        }

        $rootNormalized = rtrim(self::canonicalSeparators($root), '/');
        $pathNormalized = self::canonicalSeparators($path);
        $relative = ltrim(substr($pathNormalized, strlen($rootNormalized)), '/');
        $cursor = $rootNormalized;
        foreach (array_filter(explode('/', $relative), static fn (string $part): bool => $part !== '') as $part) {
            $cursor .= '/'.$part;
            $native = str_replace('/', DIRECTORY_SEPARATOR, $cursor);
            if (file_exists($native) || is_link($native)) {
                if (is_link($native)) {
                    throw new InvalidArgumentException("Filesystem destination traverses symbolic link [{$native}].");
                }
            } else {
                break;
            }
        }
    }

    private static function canonicalSeparators(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
