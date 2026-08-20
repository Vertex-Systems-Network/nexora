<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N029ExtensionsArchitectureTest extends TestCase
{
    public function test_extensions_marketplace_and_shared_ui_boundaries_are_present(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string)file_get_contents($root.'/config/nexora.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_15_001600_add_nexora_extensions_marketplace.php');
        $installer=(string)file_get_contents($root.'/app/Nexora/Extensions/Services/ExtensionPackageInstaller.php');
        $stager=(string)file_get_contents($root.'/app/Nexora/Extensions/Services/MarketplacePackageStager.php');
        $dataTable=(string)file_get_contents($root.'/resources/js/admin/components/data/DataTable.tsx');
        $select=(string)file_get_contents($root.'/resources/js/admin/ui/untitled/select.tsx');
        $dateTime=(string)file_get_contents($root.'/resources/js/admin/ui/untitled/date-time-picker.tsx');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('ExtensionsModule::class',$config);
        foreach(['nx_extensions','nx_extension_versions','nx_extension_dependencies','nx_extension_capability_grants','nx_extension_lifecycle_events','nx_marketplace_sources','nx_marketplace_catalog_items'] as $table) self::assertStringContainsString($table,$migration);
        self::assertStringNotContainsString('->after(',$migration);
        self::assertStringContainsString("decision !== 'allow'",$installer);
        self::assertStringContainsString('trusted_publishers_only',$stager);
        self::assertStringContainsString("where('status', 'active')",$stager);
        self::assertStringContainsString('sticky top-0',$dataTable);
        self::assertStringContainsString('sticky bottom-0',$dataTable);
        self::assertStringContainsString('react-aria-components',$select);
        self::assertStringContainsString('value={value === "" ? null : value}',$select);
        self::assertStringContainsString('onChange={(key) =>',$select);
        self::assertStringNotContainsString('selectedKey=',$select);
        self::assertStringNotContainsString('onSelectionChange=',$select);
        self::assertStringNotContainsString('nx-pressable',$select);
        self::assertStringContainsString('react-aria-components',$dateTime);
        self::assertStringContainsString('@internationalized/date',$dateTime);
        self::assertStringContainsString('| N0.29 | Extensions lifecycle, Forge developer SDK, Marketplace | DONE |',$plan);
        self::assertStringContainsString('| N0.30 | Commerce + Billing foundation | DONE |',$plan);
        foreach(['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
