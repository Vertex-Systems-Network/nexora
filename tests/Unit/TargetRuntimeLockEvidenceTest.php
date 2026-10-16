<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-lock-evidence.php';

final class TargetRuntimeLockEvidenceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/nexora-lock-evidence-'.bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
        file_put_contents($this->root.'/composer.lock', '{"lock":"composer"}');
        file_put_contents($this->root.'/package-lock.json', '{"lock":"npm"}');
    }

    protected function tearDown(): void
    {
        foreach (['composer.lock', 'package-lock.json'] as $file) {
            if (is_file($this->root.'/'.$file) || is_link($this->root.'/'.$file)) unlink($this->root.'/'.$file);
        }
        if (is_dir($this->root)) rmdir($this->root);
    }

    /** @return array<string,mixed> */
    private function environment(): array
    {
        return [
            'composer_lock_sha256' => hash_file('sha256', $this->root.'/composer.lock'),
            'package_lock_sha256' => hash_file('sha256', $this->root.'/package-lock.json'),
        ];
    }

    #[Test]
    public function complete_matching_lock_evidence_passes(): void
    {
        self::assertSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $this->environment(), true));
    }

    #[Test]
    public function missing_fingerprint_cannot_bypass_pass_verification(): void
    {
        $env = $this->environment();
        unset($env['composer_lock_sha256']);
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
        $env = $this->environment();
        unset($env['package_lock_sha256']);
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
    }

    #[Test]
    public function explicit_null_cannot_bypass_pass_verification(): void
    {
        $env = $this->environment();
        $env['composer_lock_sha256'] = null;
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
    }

    #[Test]
    public function mismatched_lock_bytes_are_rejected(): void
    {
        $env = $this->environment();
        file_put_contents($this->root.'/composer.lock', '{"changed":true}');
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
        file_put_contents($this->root.'/package-lock.json', '{"changed":true}');
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
    }

    #[Test]
    public function malformed_hashes_fail_closed(): void
    {
        foreach (['abc', str_repeat('G', 64), str_repeat('0', 63), 123] as $bad) {
            $env = $this->environment();
            $env['package_lock_sha256'] = $bad;
            self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
        }
    }

    #[Test]
    public function missing_or_symlinked_source_lock_is_rejected(): void
    {
        $env = $this->environment();
        unlink($this->root.'/composer.lock');
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
        if (function_exists('symlink') && @symlink($this->root.'/package-lock.json', $this->root.'/composer.lock')) {
            self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, $env, true));
        }
    }

    #[Test]
    public function blocked_diagnostics_can_have_absent_fingerprints_but_not_forged_ones(): void
    {
        self::assertSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, [], false));
        self::assertSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, ['composer_lock_sha256' => null], false));
        self::assertNotSame([], \nexoraTargetRuntimeLockEvidenceErrors($this->root, ['package_lock_sha256' => 'invalid'], false));
    }
}
