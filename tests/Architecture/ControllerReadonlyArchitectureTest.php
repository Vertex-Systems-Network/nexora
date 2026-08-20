<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ControllerReadonlyArchitectureTest extends TestCase
{
    #[Test]
    public function controllers_extending_the_base_controller_are_not_readonly(): void
    {
        $root = app_path('Http/Controllers');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/\b(?:final\s+)?readonly\s+class\s+\w+\s+extends\s+Controller\b/', $source) === 1) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertSame([], $violations, 'Readonly controllers cannot extend Nexora/Laravel non-readonly base Controller.');
    }
}
