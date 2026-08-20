<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DatabaseNamingTest extends TestCase
{
    public function test_migrations_do_not_create_phase_or_milestone_tables(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../database/migrations'));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/Schema::create\([\'\"](?:phase|milestone)_/i', (string) $contents, $file->getFilename());
        }
    }
}
