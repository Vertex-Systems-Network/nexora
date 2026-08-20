<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N028SupplyChainArchitectureTest extends TestCase
{
    public function test_supply_chain_security_and_stability_boundaries_are_present(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string)file_get_contents($root.'/config/nexora.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_15_001500_add_nexora_supply_chain_security.php');
        $digest=(string)file_get_contents($root.'/app/Nexora/Security/SupplyChain/Services/PackageContentDigest.php');
        $signature=(string)file_get_contents($root.'/app/Nexora/Security/SupplyChain/Services/SignatureVerifier.php');
        $schedule=(string)file_get_contents($root.'/routes/console.php');
        $media=(string)file_get_contents($root.'/app/Nexora/Media/Services/MediaUploadPolicy.php');
        $errors=(string)file_get_contents($root.'/app/Nexora/Http/ErrorPresenter.php');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        self::assertStringContainsString('SupplyChainSecurityModule::class', $config);
        foreach(['nx_trusted_publishers','nx_supply_chain_artifacts','nx_supply_chain_components','nx_supply_chain_attestations'] as $table) self::assertStringContainsString($table,$migration);
        self::assertStringContainsString("if (\$name === 'nexora.signature.json') continue;", $digest);
        self::assertStringContainsString("\$algorithm !== 'ed25519'", $signature);
        self::assertStringContainsString("->name('nexora.automation.hourly')->hourly()->withoutOverlapping()", $schedule);
        self::assertStringContainsString("->name('nexora.automation.daily')->dailyAt('00:05')->withoutOverlapping()", $schedule);
        self::assertStringContainsString('getPathname()', $media);
        self::assertStringContainsString('request_id', $errors);
        self::assertStringContainsString('| N0.28 | Sentinel advanced supply-chain controls: SBOM, signing, provenance, sandbox adapters | DONE |',$plan);
        self::assertStringContainsString('| N0.29 | Extensions lifecycle, Forge developer SDK, Marketplace | DONE |',$plan);
        foreach(['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
