<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-resume-contracts.php';

final class TargetRuntimeBuildResumeTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/nexora-build-resume-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/public/build/assets', 0700, true);
        mkdir($this->root.'/storage/app/nexora/certification', 0700, true);
        $this->put('public/build/assets/app-aaaaaaaa.js', 'console.log(1);');
        $this->put('public/build/assets/app-bbbbbbbb.css', 'body{}');
        $this->put('public/build/manifest.json', json_encode([
            'resources/js/app.tsx' => ['file' => 'assets/app-aaaaaaaa.js', 'isEntry' => true],
            'resources/css/app.css' => ['file' => 'assets/app-bbbbbbbb.css', 'isEntry' => true],
        ], JSON_THROW_ON_ERROR));
        $this->put('storage/app/nexora/certification/build-assets.json', json_encode([
            'status' => 'pass', 'platform_version' => '1.0',
            'source_tree_sha256' => str_repeat('a', 64),
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    private function put(string $name, string $contents): void
    {
        file_put_contents($this->root.'/'.$name, $contents);
    }

    /** @return array<string,mixed> */
    private function environment(): array
    {
        return ['platform_version' => '1.0', 'source_tree_sha256' => str_repeat('a', 64),
            'composer_version' => '2.9.1', 'node_version' => '22.16.0', 'npm_version' => '10.9.2',
            'node_modules' => true, 'node_modules_lock_sha256' => str_repeat('e', 64),
            'vendor_autoload' => true, 'vendor_installed_sha256' => str_repeat('f', 64)];
    }

    /** @return array<string,bool> */
    private function steps(): array
    {
        return ['npm-ci' => true, 'frontend-typecheck' => true, 'frontend-tests' => true,
            'frontend-build' => true, 'frontend-build-budget' => true];
    }

    #[Test]
    public function unchanged_valid_output_and_budget_receipt_can_be_reused(): void
    {
        $evidence = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $evidence['build_output_sha256']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $evidence['build_budget_report_sha256']);
        self::assertSame($this->steps(), \nexoraTargetResumeSafeStepAllowlist($this->environment(), $this->steps(), $evidence, $evidence));
    }

    #[Test]
    public function deleted_or_modified_asset_disables_only_build_related_step_reuse(): void
    {
        $prior = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        $this->put('public/build/assets/app-aaaaaaaa.js', 'console.log(2);');
        $current = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertNotSame($prior['build_output_sha256'], $current['build_output_sha256']);
        self::assertSame(['npm-ci' => true, 'frontend-typecheck' => true, 'frontend-tests' => true],
            \nexoraTargetResumeSafeStepAllowlist($this->environment(), $this->steps(), $prior, $current));

        unlink($this->root.'/public/build/assets/app-bbbbbbbb.css');
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function changed_budget_report_requires_budget_verification_only(): void
    {
        $prior = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        $this->put('storage/app/nexora/certification/build-assets.json', json_encode([
            'status' => 'pass', 'platform_version' => '1.0',
            'source_tree_sha256' => str_repeat('a', 64), 'changed' => true,
        ], JSON_THROW_ON_ERROR));
        $current = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        $expected = $this->steps();
        unset($expected['frontend-build-budget']);
        self::assertSame($expected,
            \nexoraTargetResumeSafeStepAllowlist($this->environment(), $this->steps(), $prior, $current));
    }

    #[Test]
    public function missing_or_failed_report_cannot_skip_budget_verification(): void
    {
        $prior = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        unlink($this->root.'/storage/app/nexora/certification/build-assets.json');
        $current = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertNull($current['build_budget_report_sha256']);
        $allowed = \nexoraTargetResumeSafeStepAllowlist($this->environment(), $this->steps(), $prior, $current);
        self::assertArrayHasKey('frontend-build', $allowed);
        self::assertArrayNotHasKey('frontend-build-budget', $allowed);
    }

    #[Test]
    public function malformed_manifest_or_development_hot_file_disables_build_reuse(): void
    {
        $this->put('public/build/manifest.json', '{invalid json');
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
        $this->put('public/build/manifest.json', json_encode([
            'resources/js/app.tsx' => ['file' => 'assets/../../.env'],
            'resources/css/app.css' => ['file' => 'assets/app-bbbbbbbb.css'],
        ], JSON_THROW_ON_ERROR));
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
        $this->put('public/hot', 'http://127.0.0.1:5173');
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function symlinked_assets_are_not_trusted_as_resume_evidence(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped('Symlinks not supported by this PHP runtime.');
        }
        unlink($this->root.'/public/build/assets/app-aaaaaaaa.js');
        if (!@symlink('/etc/hosts', $this->root.'/public/build/assets/app-aaaaaaaa.js')) {
            self::markTestSkipped('Symlink creation not allowed.');
        }
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function symlinked_parent_directory_must_not_bypass_asset_containment(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped('Symlinks not supported by this PHP runtime.');
        }
        mkdir($this->root.'/outside-assets');
        file_put_contents($this->root.'/outside-assets/app-aaaaaaaa.js', 'console.log(1);');
        file_put_contents($this->root.'/outside-assets/app-bbbbbbbb.css', 'body{}');
        rename($this->root.'/public/build/assets', $this->root.'/real-assets');
        if (!@symlink($this->root.'/outside-assets', $this->root.'/public/build/assets')) {
            self::markTestSkipped('Symlink creation not allowed.');
        }
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function stale_source_bound_budget_report_must_not_be_reused(): void
    {
        $bad = ['platform_version' => '1.0', 'source_tree_sha256' => str_repeat('b', 64)];
        self::assertNull(\nexoraTargetResumeOutputArtifacts($this->root, $bad)['build_budget_report_sha256']);
        $old = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertArrayNotHasKey('frontend-build-budget', \nexoraTargetResumeSafeStepAllowlist($this->environment(), $this->steps(), [], $old));
    }
}
