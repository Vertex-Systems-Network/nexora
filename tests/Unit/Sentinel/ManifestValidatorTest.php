<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\ManifestValidator;
use Tests\TestCase;

final class ManifestValidatorTest extends TestCase
{
    public function test_missing_manifest_fails_closed(): void
    {
        $result = (new ManifestValidator())->validate(null);
        self::assertSame([], $result['manifest']);
        self::assertTrue($result['findings'][0]->hardBlock);
    }

    public function test_valid_manifest_passes_schema_checks(): void
    {
        $result = (new ManifestValidator())->validate(json_encode([
            'schema' => '1.0',
            'id' => 'acme.safe-extension',
            'name' => 'Safe Extension',
            'type' => 'extension',
            'version' => '1.0.0',
            'capabilities' => [],
        ], JSON_THROW_ON_ERROR));

        self::assertSame('acme.safe-extension', $result['manifest']['id']);
        self::assertSame([], $result['findings']);
    }
}
