<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimeBootstrapArchitectureTest extends TestCase
{
    #[Test]
    public function required_laravel_runtime_paths_exist_and_are_writable(): void
    {
        $paths = [
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('framework/cache/data'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            self::assertDirectoryExists($path, "Required runtime directory is missing: {$path}");
            self::assertTrue(is_writable($path), "Required runtime directory is not writable: {$path}");
        }
    }

    #[Test]
    public function compiled_view_path_is_explicit_and_valid(): void
    {
        $compiled = (string) config('view.compiled');

        self::assertSame(str_replace('\\', '/', storage_path('framework/views')), str_replace('\\', '/', $compiled));
        self::assertDirectoryExists($compiled);
        self::assertTrue(is_writable($compiled));
    }

    #[Test]
    public function http_and_cli_entrypoints_boot_runtime_repair_before_laravel(): void
    {
        foreach ([base_path('artisan'), public_path('index.php')] as $entryPoint) {
            $source = (string) file_get_contents($entryPoint);
            self::assertStringContainsString('nexora-runtime-bootstrap.php', $source);
        }
    }
}
