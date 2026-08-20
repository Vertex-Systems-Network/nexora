<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Filesystem\PortablePath;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PortablePathTest extends TestCase
{
    #[Test]
    public function portable_relative_paths_normalize_cross_platform_separators(): void
    {
        self::assertSame('assets/icons/logo.svg',PortablePath::normalizeRelative('assets\\icons\\logo.svg'));
    }

    /** @return list<array{string}> */
    public static function unsafePaths(): array
    {
        return [['../secret'],['/absolute'],['C:/windows'],['assets/../secret'],['CON.txt'],['folder/trailing.'],['folder/trailing '],['folder/a:b']];
    }

    #[Test]
    #[DataProvider('unsafePaths')]
    public function non_portable_or_traversal_paths_are_rejected(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        PortablePath::normalizeRelative($path);
    }
}
