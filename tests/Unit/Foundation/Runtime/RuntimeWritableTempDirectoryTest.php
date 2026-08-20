<?php

declare(strict_types=1);

namespace Tests\Unit\Foundation\Runtime;

use App\Nexora\Foundation\Runtime\RuntimeWritableTempDirectory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeWritableTempDirectoryTest extends TestCase
{
    #[Test]
    public function installer_falls_back_from_an_invalid_configured_temp_path_to_app_storage(): void
    {
        $invalid = storage_path('framework/testing-temp-file');
        @mkdir(dirname($invalid), 0755, true);
        file_put_contents($invalid, 'not-a-directory');
        config()->set('nexora-host-runtime.installation.temp_directory', $invalid);

        try {
            $state = app(RuntimeWritableTempDirectory::class)->installation();

            self::assertSame('pass', $state['status']);
            self::assertNotSame($invalid, $state['selected_path']);
            self::assertTrue(is_dir((string) $state['selected_path']));
            self::assertTrue(is_writable((string) $state['selected_path']));
        } finally {
            @unlink($invalid);
        }
    }
}
