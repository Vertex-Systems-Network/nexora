<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class Laravel13PathHelperTest extends TestCase
{
    public function test_application_source_does_not_use_unavailable_bootstrap_path_helper(): void
    {
        $roots = ['app', 'bootstrap', 'config', 'database', 'routes'];
        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/'.$root));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $source = (string) file_get_contents($file->getPathname());
                self::assertDoesNotMatchRegularExpression('/\bbootstrap_path\s*\(/', $source, $file->getPathname());
            }
        }
    }
}
