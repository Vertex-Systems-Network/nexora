<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class IdentityAccessArchitectureTest extends TestCase
{
    public function test_admin_feature_code_does_not_import_vendor_ui_paths(): void
    {
        $root = dirname(__DIR__, 2).'/resources/js/admin/pages';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.tsx')) continue;
            $source = file_get_contents($file->getPathname());
            self::assertStringNotContainsString('/ui/untitled/', (string) $source, $file->getPathname());
            self::assertStringNotContainsString('@untitledui/', (string) $source, $file->getPathname());
            self::assertStringNotContainsString('@admin/ui', (string) $source, $file->getPathname());
        }
    }
}
