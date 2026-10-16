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
        $this->put('storage/app/nexora/certification/build-assets.json', json_encode($this->validReport(), JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function validReport(): array
    {
        return [
            'schema' => 2, 'status' => 'pass', 'platform_version' => '1.0',
            'source_tree_sha256' => str_repeat('a', 64),
            'budgets' => ['build_total_bytes' => 1048576],
            'totals' => ['build' => 400], 'counts' => ['js' => 1, 'css' => 1],
            'files' => [['path' => 'public/build/manifest.json', 'bytes' => 100,
                'sha256' => str_repeat('b', 64)]],
            'initial_javascript_assets' => ['assets/app-aaaaaaaa.js'],
            'errors' => [], 'warnings' => [],
        ];
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
        $report = $this->validReport();
        $report['warnings'] = ['reviewed warning'];
        $this->put('storage/app/nexora/certification/build-assets.json', json_encode($report, JSON_THROW_ON_ERROR));
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
    public function oversized_even_valid_json_manifest_is_not_reused(): void
    {
        $manifest = $this->root.'/public/build/manifest.json';
        // Valid JSON can contain arbitrary trailing whitespace. Previously it
        // was fully read and decoded without any bound before hashing assets.
        $file = fopen($manifest, 'ab');
        self::assertSame(true, is_resource($file));
        fwrite($file, str_repeat(' ', 8 * 1024 * 1024 + 1));
        fclose($file);
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function dangling_vite_hot_symlink_cannot_authorize_resume(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped('Symlinks unavailable.');
        }
        if (!@symlink($this->root.'/no-such-vite-hot', $this->root.'/public/hot')) {
            self::markTestSkipped('Symlink creation not permitted.');
        }
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function linked_report_parent_is_not_trusted_for_budget_reuse(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped('Symlinks unavailable.');
        }
        $outside = $this->root.'/outside-report';
        mkdir($outside, 0700, true);
        rename($this->root.'/storage/app/nexora/certification/build-assets.json', $outside.'/build-assets.json');
        rmdir($this->root.'/storage/app/nexora/certification');
        if (!@symlink($outside, $this->root.'/storage/app/nexora/certification')) {
            self::markTestSkipped('Symlink creation not permitted.');
        }
        $artifacts = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $artifacts['build_output_sha256']);
        self::assertNull($artifacts['build_budget_report_sha256']);
    }

    #[Test]
    public function symlinked_public_root_cannot_authorize_reused_build(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped('Symlinks unavailable.');
        }
        rename($this->root.'/public', $this->root.'/real-public');
        if (!@symlink($this->root.'/real-public', $this->root.'/public')) {
            self::markTestSkipped('Symlink creation not permitted.');
        }
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function report_claiming_pass_with_errors_is_not_resumable(): void
    {
        $report = $this->validReport();
        $report['errors'] = ['JS asset budget exceeded'];
        $this->put('storage/app/nexora/certification/build-assets.json', json_encode($report, JSON_THROW_ON_ERROR));
        $result = \nexoraTargetResumeOutputArtifacts($this->root, $this->environment());
        self::assertNull($result['build_budget_report_sha256']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['build_output_sha256']);
    }

    #[Test]
    public function wrong_or_missing_report_schema_is_not_resumable(): void
    {
        foreach ([1, null] as $schema) {
            $report = $this->validReport();
            if ($schema === null) {
                unset($report['schema']);
            } else {
                $report['schema'] = $schema;
            }
            $this->put('storage/app/nexora/certification/build-assets.json', json_encode($report, JSON_THROW_ON_ERROR));
            self::assertNull(\nexoraTargetResumeOutputArtifacts($this->root, $this->environment())['build_budget_report_sha256']);
        }
    }

    #[Test]
    public function incomplete_report_payload_is_not_resumable(): void
    {
        foreach (['budgets', 'totals', 'counts', 'files', 'initial_javascript_assets', 'errors'] as $key) {
            $report = $this->validReport();
            unset($report[$key]);
            $this->put('storage/app/nexora/certification/build-assets.json', json_encode($report, JSON_THROW_ON_ERROR));
            self::assertNull(\nexoraTargetResumeOutputArtifacts($this->root, $this->environment())['build_budget_report_sha256']);
        }
    }

    #[Test]
    public function oversized_valid_report_is_not_resumable(): void
    {
        $report = json_encode($this->validReport(), JSON_THROW_ON_ERROR);
        $file = $this->root.'/storage/app/nexora/certification/build-assets.json';
        file_put_contents($file, $report.str_repeat(' ', 8 * 1024 * 1024 + 1));
        self::assertNull(\nexoraTargetResumeOutputArtifacts($this->root, $this->environment())['build_budget_report_sha256']);
    }

    #[Test]
    public function valid_dynamic_imports_and_static_assets_remain_resumable(): void
    {
        $manifest = json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['resources/js/app.tsx']['dynamicImports'] = ['resources/js/lazy.tsx'];
        $manifest['resources/js/app.tsx']['assets'] = ['assets/logo-cccccccc.svg'];
        $manifest['resources/js/lazy.tsx'] = ['file' => 'assets/lazy-dddddddd.js'];
        $this->put('public/build/assets/lazy-dddddddd.js', 'export default 1;');
        $this->put('public/build/assets/logo-cccccccc.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', \nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function missing_dynamic_import_manifest_key_fails_closed(): void
    {
        $manifest = json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['resources/js/app.tsx']['dynamicImports'] = ['resources/js/removed.tsx'];
        $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function missing_lazy_chunk_referenced_by_manifest_fails_closed(): void
    {
        $manifest = json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['resources/js/app.tsx']['dynamicImports'] = ['resources/js/lazy.tsx'];
        $manifest['resources/js/lazy.tsx'] = ['file' => 'assets/lazy-dddddddd.js'];
        $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function missing_static_asset_referenced_by_manifest_fails_closed(): void
    {
        $manifest = json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['resources/js/app.tsx']['assets'] = ['assets/logo-cccccccc.svg'];
        $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function malformed_import_and_asset_lists_are_not_resumable(): void
    {
        foreach (['imports', 'dynamicImports', 'css', 'assets'] as $field) {
            $manifest = json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            unset($manifest['resources/js/app.tsx']['imports'], $manifest['resources/js/app.tsx']['dynamicImports'],
                  $manifest['resources/js/app.tsx']['css'], $manifest['resources/js/app.tsx']['assets']);
            $manifest['resources/js/app.tsx'][$field] = 'assets/app-aaaaaaaa.js';
            $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
            self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
        }
    }

    #[Test]
    public function secret_dotenv_file_disables_reuse_even_when_manifest_is_intact(): void
    {
        $this->put('public/build/.env', 'FAKE_TEST_VALUE=not-a-secret');
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function unreferenced_php_file_disables_build_reuse(): void
    {
        $this->put('public/build/assets/debug.php', '<?php echo 1;');
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function manifest_reference_to_json_configuration_disables_reuse(): void
    {
        $manifest=json_decode((string)file_get_contents($this->root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['resources/js/app.tsx']['assets']=['assets/config.json'];
        $this->put('public/build/assets/config.json', '{"token":"synthetic"}');
        $this->put('public/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        self::assertNull(\nexoraTargetResumeBuildDigest($this->root));
    }

    #[Test]
    public function regular_generated_font_remains_resumable(): void
    {
        $this->put('public/build/assets/site.woff2', 'synthetic-font');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', \nexoraTargetResumeBuildDigest($this->root));
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
