<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdminPageProductionBoundaryArchitectureTest extends TestCase
{
    #[Test]
    public function no_test_fixture_can_be_discovered_as_a_deployable_inertia_page(): void
    {
        $root = resource_path('js/admin/pages');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile()) continue;
            self::assertDoesNotMatchRegularExpression('/\.(?:test|spec)\.[jt]sx?$/i', $file->getFilename(), 'A test file under admin/pages can be emitted to public/build by the Inertia Vite resolver: '.$file->getPathname());
        }
        self::assertFileExists(resource_path('js/admin/tests/SystemHealth.test.tsx'));
    }
}
