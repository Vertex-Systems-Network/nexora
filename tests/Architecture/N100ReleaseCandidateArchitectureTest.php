<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100ReleaseCandidateArchitectureTest extends TestCase
{
    public function test_release_candidate_certification_boundaries_are_present(): void
    {
        $root=dirname(__DIR__,2);
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $release=(string)file_get_contents($root.'/scripts/build-production-release.php');
        $zeroBat=(string)file_get_contents($root.'/scripts/setup-zero.bat');
        $baseTheme=(string)file_get_contents($root.'/themes/nexora-base/nexora.json');
        $enterprise=(string)file_get_contents($root.'/app/Nexora/Modules/Core/EnterpriseModule.php');
        $certRunner=(string)file_get_contents($root.'/scripts/certify-release.php');

        foreach(['scripts/certification-preflight.php','scripts/module-graph-verify.php','scripts/frontend-contract-verify.php','scripts/laravel-runtime-contract-verify.php','scripts/database-contract-verify.php','scripts/security-contract-verify.php','scripts/zero-install-contract-verify.php','scripts/certify-release.php','scripts/create-certification-database.php','scripts/http-smoke.php','scripts/zero-state-verify.php','scripts/target-diagnostics.php','scripts/target-diagnostics-contract-verify.php','scripts/upgrade-contract-verify.php','scripts/environment-contract-verify.php','scripts/dependency-contract-verify.php','scripts/dependency-runtime-verify.php','scripts/dependency-provenance.php','scripts/dependency-audit.php','scripts/filesystem-contract-verify.php','scripts/final-integrity-contract-verify.php','scripts/source-attestation-contract-verify.php','scripts/source-attestation.php','scripts/release-artifact-verify.php','scripts/zero-install-evidence-verify.php','scripts/upgrade-rehearsal-evidence-verify.php'] as $relative) {
            self::assertFileExists($root.'/'.$relative);
        }
        self::assertStringContainsString("\$configPath = \$root.'/config/nexora.php';",$release);
        self::assertStringContainsString('certification-pass',$release);
        self::assertStringNotContainsString("\$version = '0.26.0'",$release);
        self::assertStringNotContainsString('copy .env.example .env',$zeroBat);
        self::assertStringContainsString('zero-state-verify.php',$zeroBat);
        self::assertStringContainsString('>=0.34 <2.0',$baseTheme);
        self::assertStringContainsString("new ModuleDependency('nexora.identity-access','^0.5')",$enterprise);
        self::assertStringContainsString("'module-graph'",$certRunner);
        self::assertStringContainsString("'laravel-runtime-contract'",$certRunner);
        self::assertStringContainsString("'database-contract'",$certRunner);
        self::assertStringContainsString("'security-contract'",$certRunner);
        self::assertStringContainsString("'zero-install-contract'",$certRunner);
        self::assertStringContainsString("'performance-contract'",$certRunner);
        self::assertStringContainsString("'performance-build'",$certRunner);
        self::assertStringContainsString("'final-closure-contract'",$certRunner);
        self::assertStringContainsString("'target-diagnostics-contract'",$certRunner);
        self::assertStringContainsString("'upgrade-contract'",$certRunner);
        self::assertStringContainsString("'dependency-contract'",$certRunner);
        self::assertStringContainsString("'filesystem-contract'",$certRunner);
        self::assertStringContainsString("'final-integrity-contract'",$certRunner);
        self::assertStringContainsString("'source-attestation-contract'",$certRunner);
        self::assertStringContainsString("'dependency-provenance'",$certRunner);
        self::assertStringContainsString("'dependency-audit'",$certRunner);
        self::assertStringContainsString('| N1.0 | Release Candidate certification', $plan);
        self::assertStringContainsString('CERTIFYING', $plan);
        self::assertStringContainsString('| N1.1 | Admin UX / Design System certification', $plan);
        foreach(['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external,$plan);
    }
}
