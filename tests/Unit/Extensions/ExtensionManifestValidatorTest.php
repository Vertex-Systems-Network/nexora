<?php

declare(strict_types=1);

namespace Tests\Unit\Extensions;

use App\Nexora\Extensions\Services\ExtensionManifestValidator;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use RuntimeException;
use Tests\TestCase;

final class ExtensionManifestValidatorTest extends TestCase
{
    public function test_it_accepts_a_compatible_declarative_extension_manifest(): void
    {
        config()->set('nexora.version', '0.29.0');
        $manifest = (new ExtensionManifestValidator(new VersionConstraintMatcher()))->validate([
            'id'=>'vendor.search-tools','name'=>'Search Tools','type'=>'extension','version'=>'1.2.0','description'=>'Example extension.',
            'requires'=>['nexora'=>'^0.29'],'runtime'=>['mode'=>'declarative'],'capabilities'=>['search.index.read'],
            'dependencies'=>['vendor.shared'=>'^1.0'],'migrations'=>['policy'=>'none','schema_compatible_rollback'=>false],
        ]);

        self::assertSame('vendor.search-tools', $manifest->identifier);
        self::assertSame('declarative', $manifest->runtimeMode);
        self::assertSame(['search.index.read'], $manifest->capabilities);
        self::assertSame(['vendor.shared'=>'^1.0'], $manifest->dependencies);
    }

    public function test_it_rejects_an_incompatible_nexora_constraint(): void
    {
        config()->set('nexora.version', '0.29.0');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Package requires Nexora');
        (new ExtensionManifestValidator(new VersionConstraintMatcher()))->validate([
            'id'=>'vendor.future','name'=>'Future','type'=>'extension','version'=>'1.0.0','requires'=>['nexora'=>'^1.0'],
        ]);
    }

    public function test_it_rejects_reverse_or_destructive_migration_policies(): void
    {
        config()->set('nexora.version', '0.29.0');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('none or forward-only');
        (new ExtensionManifestValidator(new VersionConstraintMatcher()))->validate([
            'id'=>'vendor.unsafe','name'=>'Unsafe','type'=>'extension','version'=>'1.0.0','requires'=>['nexora'=>'^0.29'],
            'migrations'=>['policy'=>'rollback-destructive'],
        ]);
    }
}
