<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N019SeoArchitectureTest extends TestCase
{
    public function test_seo_is_registered_as_core_contract_layer_and_external_apps_stay_out_of_core_roadmap(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');

        self::assertStringContainsString('SeoModule::class', $config);
        self::assertStringContainsString('seo.metadata.read', $config);
        self::assertStringContainsString('EXT-B01', $plan);
        self::assertStringContainsString('EXT-P01', $plan);
        self::assertStringContainsString('EXT-L01', $plan);
        self::assertStringContainsString('EXT-BK01', $plan);
        self::assertStringContainsString('EXT-PR01', $plan);
    }
}
