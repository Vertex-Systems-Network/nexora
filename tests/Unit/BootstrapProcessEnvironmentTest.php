<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BootstrapProcessEnvironmentTest extends TestCase
{
    #[Test]
    public function bootstrap_process_environment_always_provides_composer_and_npm_homes(): void
    {
        require_once base_path('bootstrap/nexora-process-environment.php');

        $previousComposerHome = getenv('COMPOSER_HOME');
        $previousComposerCache = getenv('COMPOSER_CACHE_DIR');
        $previousNpmCache = getenv('NPM_CONFIG_CACHE');

        try {
            putenv('COMPOSER_HOME');
            putenv('COMPOSER_CACHE_DIR');
            putenv('NPM_CONFIG_CACHE');

            $environment = \NexoraBootstrapProcessEnvironment::build(base_path());

            self::assertTrue(
                PHP_OS_FAMILY === 'Windows'
                    ? (($environment['COMPOSER_HOME'] ?? '') !== '' || ($environment['APPDATA'] ?? '') !== '')
                    : (($environment['HOME'] ?? '') !== ''),
            );
            self::assertNotSame('', $environment['COMPOSER_CACHE_DIR'] ?? '');
            self::assertNotSame('', $environment['NPM_CONFIG_CACHE'] ?? '');
            $summary = \NexoraBootstrapProcessEnvironment::summary(base_path());
            self::assertNotSame('', $summary['composer_home']);
            self::assertTrue($summary['composer_home_writable']);
            self::assertDirectoryExists($environment['COMPOSER_CACHE_DIR']);
            self::assertDirectoryExists($environment['NPM_CONFIG_CACHE']);
        } finally {
            $this->restoreEnvironment('COMPOSER_HOME', $previousComposerHome);
            $this->restoreEnvironment('COMPOSER_CACHE_DIR', $previousComposerCache);
            $this->restoreEnvironment('NPM_CONFIG_CACHE', $previousNpmCache);
        }
    }

    #[Test]
    public function explicit_composer_home_is_preserved(): void
    {
        require_once base_path('bootstrap/nexora-process-environment.php');

        $previous = getenv('COMPOSER_HOME');
        $custom = storage_path('app/nexora/test-composer-home');

        try {
            putenv('COMPOSER_HOME='.$custom);
            $environment = \NexoraBootstrapProcessEnvironment::build(base_path());
            self::assertSame($custom, $environment['COMPOSER_HOME']);
        } finally {
            $this->restoreEnvironment('COMPOSER_HOME', $previous);
            @rmdir($custom);
        }
    }

    private function restoreEnvironment(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            return;
        }

        putenv($key.'='.$value);
    }
}
