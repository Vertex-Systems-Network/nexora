<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\NpmManifestScanner;
use PHPUnit\Framework\TestCase;

final class NpmManifestScannerTest extends TestCase
{
    public function test_install_lifecycle_script_is_hard_blocked(): void
    {
        $json = json_encode(['scripts' => ['postinstall' => 'node setup.js']], JSON_THROW_ON_ERROR);
        $findings = (new NpmManifestScanner())->scan($json);

        self::assertNotEmpty($findings);
        self::assertSame('NEX-NPM-0010', $findings[0]->ruleId);
        self::assertTrue($findings[0]->hardBlock);
    }

    public function test_package_without_lifecycle_scripts_is_clean(): void
    {
        $json = json_encode(['scripts' => ['build' => 'vite build']], JSON_THROW_ON_ERROR);
        self::assertSame([], (new NpmManifestScanner())->scan($json));
    }
}
