<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Themes\Services\ThemeManifestValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ThemeManifestValidatorTest extends TestCase
{
    public function test_theme_identity_must_match_sentinel_package_identity(): void
    {
        $validator = new ThemeManifestValidator();
        $this->expectException(InvalidArgumentException::class);
        $validator->parse(json_encode([
            'id' => 'vendor.theme', 'name' => 'Theme', 'version' => '1.0.0', 'engine' => 'nexora-safe-html',
            'templates' => ['home' => 'templates/home.html', 'document' => 'templates/document.html'],
        ], JSON_THROW_ON_ERROR), ['type' => 'theme', 'id' => 'other.theme', 'version' => '1.0.0', 'requires' => ['nexora' => '^0.20']]);
    }

    public function test_theme_design_tokens_reject_css_injection_defaults(): void
    {
        $validator = new ThemeManifestValidator();
        $this->expectException(InvalidArgumentException::class);
        $validator->parse(json_encode([
            'id' => 'vendor.theme', 'name' => 'Theme', 'version' => '1.0.0', 'engine' => 'nexora-safe-html',
            'templates' => ['home' => 'templates/home.html', 'document' => 'templates/document.html'],
            'design_tokens' => ['font.family' => ['type' => 'text', 'default' => 'Inter;}body{display:none']],
        ], JSON_THROW_ON_ERROR), ['type' => 'theme', 'id' => 'vendor.theme', 'version' => '1.0.0', 'requires' => ['nexora' => '^0.20']]);
    }
    public function test_theme_manifest_persists_nexora_compatibility_for_future_upgrade_preflight(): void
    {
        $validator = new ThemeManifestValidator();
        $manifest=$validator->parse(json_encode([
            'id'=>'vendor.theme','name'=>'Theme','version'=>'1.0.0','engine'=>'nexora-safe-html',
            'templates'=>['home'=>'templates/home.html','document'=>'templates/document.html'],
        ],JSON_THROW_ON_ERROR),['type'=>'theme','id'=>'vendor.theme','version'=>'1.0.0','requires'=>['nexora'=>'>=0.34 <2.0']]);
        self::assertSame('>=0.34 <2.0',$manifest->toArray()['requires']['nexora']??null);
    }

}
