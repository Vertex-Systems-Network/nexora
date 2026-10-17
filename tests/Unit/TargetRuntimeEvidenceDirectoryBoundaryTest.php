<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

require_once __DIR__.'/../../scripts/lib/source-attestation.php';

final class TargetRuntimeEvidenceDirectoryBoundaryTest extends TestCase
{
    private string $fixtureRoot;
    private string $bundleDir;
    private string $externalDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureRoot = sys_get_temp_dir().'/nexora-dir-proof-'.bin2hex(random_bytes(6));
        $this->bundleDir = $this->fixtureRoot.'/bundle';
        $this->externalDir = $this->fixtureRoot.'/external';
        mkdir($this->bundleDir.'/steps', 0700, true);
        mkdir($this->externalDir, 0700, true);
        $root = dirname(__DIR__, 2);
        $version = (require $root.'/config/nexora.php')['version'];
        $attestation = \nexoraComputeSourceAttestation($root);

        file_put_contents($this->bundleDir.'/summary.json', json_encode([
            'platform_version' => $version, 'status' => 'blocked',
            'steps' => [['id' => 'test', 'stdout_log' => 'steps/test.stdout.log', 'stderr_log' => 'steps/test.stderr.log']],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/environment.json', json_encode([
            'platform_version' => $version,
            'source_tree_sha256' => $attestation['tree_sha256'],
            // PASS receipts now bind dependency locks independently from the
            // general source-tree attestation (which excludes lockfiles).
            'composer_lock_sha256' => hash_file('sha256', $root.'/composer.lock'),
            'package_lock_sha256' => hash_file('sha256', $root.'/package-lock.json'),
        ], JSON_THROW_ON_ERROR));
        // The real target-runtime producer writes the exact same environment
        // fingerprint to environment.json and to summary.json.environment.
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $summary['environment'] = json_decode(file_get_contents($this->bundleDir.'/environment.json'), true, 512, JSON_THROW_ON_ERROR);
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/steps/test.stdout.log', 'synthetic stdout only');
        file_put_contents($this->bundleDir.'/steps/test.stderr.log', 'synthetic stderr only');
    }

    protected function tearDown(): void
    {
        if (isset($this->fixtureRoot) && is_dir($this->fixtureRoot)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->fixtureRoot, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $entry) {
                if ($entry->isDir() && ! $entry->isLink()) {
                    @rmdir($entry->getPathname());
                } else {
                    @unlink($entry->getPathname());
                }
            }
            @rmdir($this->fixtureRoot);
        }
        parent::tearDown();
    }

    private function verify(string $path, bool $requirePass = false): Process
    {
        $args = [PHP_BINARY, dirname(__DIR__, 2).'/scripts/target-runtime-evidence-verify.php', '--input='.$path];
        if ($requirePass) {
            $args[] = '--require-pass';
        }
        $process = new Process($args);
        $process->setTimeout(30);
        $process->run();
        return $process;
    }


    /**
     * The PASS verifier accepts only complete, ordered producer receipts with
     * environment modes and SHA-256-bound per-step logs. Legacy one-step
     * synthetic summaries remain valid only as blocked-path test fixtures.
     *
     * @return array<string,mixed>
     */
    private function prepareCompletePassReceipt(bool $full = false): array
    {
        $environment = json_decode((string) file_get_contents($this->bundleDir.'/environment.json'), true, 512, JSON_THROW_ON_ERROR);
        $environment['full_certification_requested'] = $full;
        $environment['install_dependencies_requested'] = false;
        $ids = [
            'source-preflight', 'target-bootstrap', 'target-runtime-contract', 'inertia-contract',
            'dependency-lock-review', 'dependency-contract-strict', 'dependency-runtime',
            'frontend-typecheck', 'frontend-tests', 'frontend-build', 'frontend-build-budget',
            'package-discover', 'optimize-clear', 'artisan-about', 'route-list', 'schedule-list',
            'database-doctor', 'environment-doctor', 'filesystem-doctor', 'transfer-doctor',
            'runtime-doctor', 'concurrency-doctor',
        ];
        if ($full) {
            $ids[] = 'isolated-full-certification';
        }
        $ids[] = 'closure-status';
        $steps = [];
        foreach ($ids as $id) {
            $stdout = "synthetic {$id} stdout\n";
            $stderr = '';
            file_put_contents($this->bundleDir.'/steps/'.$id.'.stdout.log', $stdout);
            file_put_contents($this->bundleDir.'/steps/'.$id.'.stderr.log', $stderr);
            $steps[] = [
                'id' => $id,
                'required' => $id !== 'closure-status',
                'status' => 'pass',
                'exit_code' => 0,
                'stdout_log' => 'steps/'.$id.'.stdout.log',
                'stderr_log' => 'steps/'.$id.'.stderr.log',
                'stdout_sha256' => hash('sha256', $stdout),
                'stderr_sha256' => hash('sha256', $stderr),
            ];
        }
        $summary = [
            'schema' => 2,
            'platform_version' => $environment['platform_version'],
            'run_id' => '20261016-150000-a1b2c3',
            'status' => $full ? 'target-certification-pass' : 'target-readiness-pass',
            'first_blocker' => null,
            'environment' => $environment,
            'steps' => $steps,
        ];
        file_put_contents($this->bundleDir.'/environment.json', json_encode($environment, JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));
        return $summary;
    }

    #[Test]
    public function symlinked_environment_json_outside_evidence_directory_is_rejected(): void
    {
        $this->requireSymlinks();
        file_put_contents($this->externalDir.'/environment.json', file_get_contents($this->bundleDir.'/environment.json'));
        unlink($this->bundleDir.'/environment.json');
        self::assertTrue(symlink($this->externalDir.'/environment.json', $this->bundleDir.'/environment.json'));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe environment.json', $result->getErrorOutput());
    }

    #[Test]
    public function symlinked_step_log_outside_evidence_directory_is_rejected(): void
    {
        $this->requireSymlinks();
        file_put_contents($this->externalDir.'/outside.log', 'synthetic external log');
        unlink($this->bundleDir.'/steps/test.stdout.log');
        self::assertTrue(symlink($this->externalDir.'/outside.log', $this->bundleDir.'/steps/test.stdout.log'));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe stdout_log', $result->getErrorOutput());
    }

    #[Test]
    public function symlinked_intermediate_steps_directory_is_rejected(): void
    {
        $this->requireSymlinks();
        file_put_contents($this->externalDir.'/test.stdout.log', 'synthetic external log');
        file_put_contents($this->externalDir.'/test.stderr.log', 'synthetic external error log');
        unlink($this->bundleDir.'/steps/test.stdout.log');
        unlink($this->bundleDir.'/steps/test.stderr.log');
        rmdir($this->bundleDir.'/steps');
        self::assertTrue(symlink($this->externalDir, $this->bundleDir.'/steps'));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe stdout_log', $result->getErrorOutput());
    }

    #[Test]
    public function symlinked_root_directory_is_rejected(): void
    {
        $this->requireSymlinks();
        $alias = $this->fixtureRoot.'/alias';
        self::assertTrue(symlink($this->bundleDir, $alias));
        $result = $this->verify($alias);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('directory symlink rejected', $result->getErrorOutput());
    }

    #[Test]
    public function path_traversal_in_step_log_is_rejected(): void
    {
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $summary['steps'][0]['stdout_log'] = '../external/outside.log';
        file_put_contents($this->externalDir.'/outside.log', 'synthetic external log');
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe stdout_log', $result->getErrorOutput());
    }

    #[Test]
    public function legitimate_directory_evidence_keeps_structural_validation_behavior(): void
    {
        $result = $this->verify($this->bundleDir);
        self::assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        self::assertStringContainsString('PASS — blocked;', $result->getOutput());

        $rejectedAsTarget = $this->verify($this->bundleDir, true);
        self::assertSame(1, $rejectedAsTarget->getExitCode());
        self::assertStringContainsString('not PASS', $rejectedAsTarget->getErrorOutput());
    }

    #[Test]
    public function oversized_directory_summary_is_rejected_before_json_decode(): void
    {
        $file = fopen($this->bundleDir.'/summary.json', 'c+b');
        self::assertNotFalse($file);
        self::assertTrue(ftruncate($file, 16 * 1024 * 1024 + 1));
        fclose($file);

        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe summary.json', $result->getErrorOutput());
    }

    #[Test]
    public function oversized_directory_step_log_is_rejected(): void
    {
        $file = fopen($this->bundleDir.'/steps/test.stdout.log', 'c+b');
        self::assertNotFalse($file);
        self::assertTrue(ftruncate($file, 16 * 1024 * 1024 + 1));
        fclose($file);

        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('unsafe stdout_log', $result->getErrorOutput());
    }

    #[Test]
    public function aggregate_directory_log_budget_is_enforced(): void
    {
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $summary['steps'] = [];
        for ($index = 0; $index < 17; $index++) {
            $name = "test-{$index}.stdout.log";
            $handle = fopen($this->bundleDir.'/steps/'.$name, 'c+b');
            self::assertNotFalse($handle);
            self::assertTrue(ftruncate($handle, 16 * 1024 * 1024));
            fclose($handle);
            $summary['steps'][] = [
                'id' => 'step-'.$index,
                'stdout_log' => 'steps/'.$name,
                'stderr_log' => 'steps/test.stderr.log',
            ];
        }
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('directory total-size limit exceeded', $result->getErrorOutput());
    }

    #[Test]
    public function excessive_directory_step_count_is_rejected(): void
    {
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $summary['steps'] = [];
        for ($index = 0; $index < 512; $index++) {
            $summary['steps'][] = [
                'id' => 'step-'.$index,
                'stdout_log' => 'steps/test.stdout.log',
                'stderr_log' => 'steps/test.stderr.log',
            ];
        }
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));
        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('too many runtime steps', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_readiness_without_composer_lock_hash_is_rejected(): void
    {
        $summary = $this->prepareCompletePassReceipt();
        unset($summary['environment']['composer_lock_sha256']);
        $environment = $summary['environment'];
        file_put_contents($this->bundleDir.'/environment.json', json_encode($environment, JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('missing or invalid composer.lock digest', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_readiness_without_package_lock_hash_is_rejected(): void
    {
        $summary = $this->prepareCompletePassReceipt();
        unset($summary['environment']['package_lock_sha256']);
        file_put_contents($this->bundleDir.'/environment.json', json_encode($summary['environment'], JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('missing or invalid package-lock.json digest', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_readiness_with_mismatched_lock_digest_is_rejected(): void
    {
        $summary = $this->prepareCompletePassReceipt();
        $summary['environment']['composer_lock_sha256'] = str_repeat('0', 64);
        file_put_contents($this->bundleDir.'/environment.json', json_encode($summary['environment'], JSON_THROW_ON_ERROR));
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('composer.lock digest does not match current source', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_readiness_cannot_hide_a_failed_required_step(): void
    {
        $summary = $this->prepareCompletePassReceipt(false);
        $summary['status'] = 'target-readiness-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][0]['required'] = true;
        $summary['steps'][0]['status'] = 'fail';
        $summary['steps'][0]['exit_code'] = 5;
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('failed mandatory step', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_readiness_cannot_hide_nonzero_required_exit_code(): void
    {
        $summary = $this->prepareCompletePassReceipt(false);
        $summary['status'] = 'target-readiness-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][0]['required'] = true;
        $summary['steps'][0]['status'] = 'pass';
        $summary['steps'][0]['exit_code'] = 9;
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('inconsistent result', $result->getErrorOutput());
    }

    #[Test]
    public function claimed_full_certification_requires_full_certification_step(): void
    {
        $summary = $this->prepareCompletePassReceipt(true);
        $summary['status'] = 'target-certification-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][count($summary['steps']) - 2]['id'] = 'invalid-certification-gate';
        $summary['steps'][0]['required'] = true;
        $summary['steps'][0]['status'] = 'pass';
        $summary['steps'][0]['exit_code'] = 0;
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('isolated-full-certification', $result->getErrorOutput());
    }

    #[Test]
    public function consistent_readiness_allows_an_optional_failed_closure_step(): void
    {
        $summary = $this->prepareCompletePassReceipt(false);
        $summary['status'] = 'target-readiness-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][0]['required'] = true;
        $summary['steps'][0]['status'] = 'pass';
        $summary['steps'][0]['exit_code'] = 0;
        $index = count($summary['steps']) - 1;
        $summary['steps'][$index]['status'] = 'fail';
        $summary['steps'][$index]['exit_code'] = 1;
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        self::assertStringContainsString('target-readiness-pass', $result->getOutput());
    }

    #[Test]
    public function consistent_full_certification_step_remains_accepted_as_structural_evidence(): void
    {
        $summary = $this->prepareCompletePassReceipt(true);
        $summary['status'] = 'target-certification-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][0]['required'] = true;
        $summary['steps'][0]['status'] = 'pass';
        $summary['steps'][0]['exit_code'] = 0;
        // The complete producer receipt already has the required certification step.
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(0, $result->getExitCode(), $result->getErrorOutput());
        self::assertStringContainsString('target-certification-pass', $result->getOutput());
    }

    #[Test]
    public function arbitrary_failed_optional_step_cannot_be_used_to_mask_failure(): void
    {
        $summary = $this->prepareCompletePassReceipt(false);
        $summary['status'] = 'target-readiness-pass';
        $summary['first_blocker'] = null;
        $summary['steps'][0]['required'] = false;
        $summary['steps'][0]['status'] = 'fail';
        $summary['steps'][0]['exit_code'] = 2;
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir, true);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('incorrect required flag', $result->getErrorOutput());
    }

    #[Test]
    public function conflicting_embedded_environment_is_rejected(): void
    {
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $summary['environment']['source_tree_sha256'] = str_repeat('f', 64);
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('environment mismatch', $result->getErrorOutput());
    }

    #[Test]
    public function missing_embedded_environment_is_rejected(): void
    {
        $summary = json_decode(file_get_contents($this->bundleDir.'/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        unset($summary['environment']);
        file_put_contents($this->bundleDir.'/summary.json', json_encode($summary, JSON_THROW_ON_ERROR));

        $result = $this->verify($this->bundleDir);
        self::assertSame(1, $result->getExitCode());
        self::assertStringContainsString('environment mismatch', $result->getErrorOutput());
    }

    private function requireSymlinks(): void
    {
        if (! function_exists('symlink')) {
            self::markTestSkipped('Symlink support is unavailable.');
        }
    }
}
