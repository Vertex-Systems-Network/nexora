<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ModuleBoundaryTest extends TestCase
{
    public function test_core_module_classes_do_not_reach_directly_into_models_or_database_facades(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Nexora/Modules'));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('use App\\Models\\', $source, $file->getPathname());
            self::assertStringNotContainsString('Illuminate\\Support\\Facades\\DB', $source, $file->getPathname());
            self::assertStringNotContainsString('Illuminate\\Support\\Facades\\Schema', $source, $file->getPathname());
        }
    }
}
