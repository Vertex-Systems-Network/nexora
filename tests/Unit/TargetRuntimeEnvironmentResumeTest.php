<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-resume-contracts.php';

final class TargetRuntimeEnvironmentResumeTest extends TestCase
{
    /** @return array<string,mixed> */
    private function environment(): array
    {
        return [
            'platform_version' => '1.0',
            'source_tree_sha256' => str_repeat('a', 64),
            'composer_version' => '2.9.1', 'node_version' => '22.16.0', 'npm_version' => '10.9.2',
            'php_version' => '8.4.24',
            'os_family' => 'Linux',
            'php_binary' => '/usr/bin/php',
            'php_extensions_sha256' => str_repeat('b', 64),
            'composer_available' => true,
            'node_available' => true,
            'npm_available' => true,
            'composer_lock_sha256' => str_repeat('c', 64),
            'package_lock_sha256' => str_repeat('d', 64),
            'vendor_installed_sha256' => str_repeat('e', 64),
            'node_modules_lock_sha256' => str_repeat('f', 64),
            'vendor_autoload' => true,
            'node_modules' => true,
            'install_dependencies_requested' => true,
            'full_certification_requested' => false,
        ];
    }

    #[Test]
    public function identical_environment_is_resumable(): void
    {
        $env = $this->environment();
        self::assertNull(\nexoraTargetResumeEnvironmentDrift($env, $env));
    }

    #[Test]
    public function php_version_or_operating_system_drift_prevents_reuse(): void
    {
        $current = $this->environment();
        $previous = $current;
        $previous['php_version'] = '8.3.11';
        self::assertSame('php_version', \nexoraTargetResumeEnvironmentDrift($previous, $current));
        $previous = $current;
        $previous['os_family'] = 'Windows';
        self::assertSame('os_family', \nexoraTargetResumeEnvironmentDrift($previous, $current));
    }

    #[Test]
    public function a_different_php_binary_or_extension_set_prevents_reuse(): void
    {
        $current = $this->environment();
        $previous = $current;
        $previous['php_binary'] = 'C:\\php\\php.exe';
        self::assertSame('php_binary', \nexoraTargetResumeEnvironmentDrift($previous, $current));
        $previous = $current;
        $previous['php_extensions_sha256'] = str_repeat('0', 64);
        self::assertSame('php_extensions_sha256', \nexoraTargetResumeEnvironmentDrift($previous, $current));
    }

    #[Test]
    public function absent_key_must_not_be_treated_as_matching_null(): void
    {
        $current = $this->environment();
        $current['vendor_installed_sha256'] = null;
        $previous = $current;
        unset($previous['vendor_installed_sha256']);
        self::assertSame('vendor_installed_sha256', \nexoraTargetResumeEnvironmentDrift($previous, $current));
        $previous = $current;
        unset($current['vendor_installed_sha256']);
        self::assertSame('vendor_installed_sha256', \nexoraTargetResumeEnvironmentDrift($previous, $current));
    }

    #[Test]
    public function dependency_availability_changes_prevent_reuse(): void
    {
        $current = $this->environment();
        foreach (['composer_available', 'node_available', 'npm_available', 'vendor_autoload', 'node_modules'] as $key) {
            $previous = $current;
            $previous[$key] = false;
            self::assertSame($key, \nexoraTargetResumeEnvironmentDrift($previous, $current), $key);
        }
    }

    #[Test]
    public function changing_install_dependency_mode_requires_new_run(): void
    {
        $current = $this->environment();
        $previous = $current;
        $previous['install_dependencies_requested'] = false;
        self::assertSame('install_dependencies_requested', \nexoraTargetResumeEnvironmentDrift($previous, $current));
    }

    #[Test]
    public function readiness_to_full_certification_mode_drift_forces_new_run(): void
    {
        $current = $this->environment();
        $current['full_certification_requested'] = true;
        self::assertSame('full_certification_requested',
            \nexoraTargetResumeEnvironmentDrift($this->environment(), $current));
        self::assertSame('full_certification_requested',
            \nexoraTargetResumeEnvironmentDrift($current, $this->environment()));
    }

    #[Test]
    public function absent_full_certification_flag_disables_resume(): void
    {
        $current = $this->environment();
        $previous = $current;
        unset($previous['full_certification_requested']);
        self::assertSame('full_certification_requested',
            \nexoraTargetResumeEnvironmentDrift($previous, $current));
        unset($current['full_certification_requested']);
        self::assertSame('full_certification_requested',
            \nexoraTargetResumeEnvironmentDrift($current, $current));
    }

    #[Test]
    public function non_boolean_execution_modes_must_fail_closed(): void
    {
        foreach (['full_certification_requested', 'install_dependencies_requested'] as $key) {
            foreach (['false', 0, null] as $bad) {
                $invalid = $this->environment();
                $invalid[$key] = $bad;
                self::assertSame($key,
                    \nexoraTargetResumeEnvironmentDrift($invalid, $invalid));
            }
        }
    }

    #[Test]
    public function installed_dependency_attestations_are_required_for_skipping(): void
    {
        $env = $this->environment();
        $allowed = [
            'composer-install' => true, 'npm-ci' => true,
            'frontend-tests' => true, 'frontend-build' => true,
        ];
        $artifacts = ['build_output_sha256' => str_repeat('1', 64),
            'build_budget_report_sha256' => str_repeat('2', 64)];
        self::assertSame($allowed, \nexoraTargetResumeSafeStepAllowlist($env, $allowed, $artifacts, $artifacts));

        $env['vendor_installed_sha256'] = null;
        $filtered = \nexoraTargetResumeSafeStepAllowlist($env, $allowed, $artifacts, $artifacts);
        self::assertArrayNotHasKey('composer-install', $filtered);
        self::assertArrayHasKey('npm-ci', $filtered);

        $env = $this->environment();
        $env['node_modules_lock_sha256'] = null;
        $filtered = \nexoraTargetResumeSafeStepAllowlist($env, $allowed, $artifacts, $artifacts);
        self::assertSame(['composer-install' => true], $filtered);
    }

    #[Test]
    public function a_missing_vendor_autoload_or_node_modules_directory_must_not_skip_install(): void
    {
        $env = $this->environment();
        $env['vendor_autoload'] = false;
        $env['node_modules'] = false;
        $allowed = ['composer-install' => true, 'npm-ci' => true, 'frontend-tests' => true];
        self::assertSame([], \nexoraTargetResumeSafeStepAllowlist($env, $allowed));
    }
}
