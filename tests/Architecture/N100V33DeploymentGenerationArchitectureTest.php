<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V33DeploymentGenerationArchitectureTest extends TestCase
{
    #[Test]
    public function deployment_generation_contract_is_fail_closed(): void
    {
        $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('scripts/n1-target-deployment-generation-contract-verify.php'));
        exec($command,$output,$exitCode);self::assertSame(0,$exitCode,implode("\n",$output));
    }

    #[Test]
    public function inertia_uses_runtime_asset_version_for_stale_bundle_reload(): void
    {
        $middleware=(string)file_get_contents(base_path('app/Http/Middleware/HandleInertiaRequests.php'));
        self::assertStringContainsString('public function version(Request $request)',$middleware);
        self::assertStringContainsString('assetVersion()',$middleware);
    }
}
