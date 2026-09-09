<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use App\Nexora\Security\Sentinel\Support\SentinelApprovalGuard;
use App\Nexora\Security\Sentinel\Support\SentinelFailureReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class SentinelTrustHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function test_current_allow_scan_can_be_promoted(): void
    {
        [$package, $scan] = $this->packageWithAllowScan('sentinel-current');

        app(SentinelApprovalGuard::class)->assertCurrent($package, $scan);

        $this->assertTrue(true);
    }

    public function test_old_allow_scan_is_rejected_after_newer_scan_exists(): void
    {
        [$package, $oldAllow] = $this->packageWithAllowScan('sentinel-stale');
        $this->createScan($package, 'block', now()->addSecond());
        $package->forceFill(['status' => 'scanned'])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('newer or ambiguously concurrent Sentinel scan');
        app(SentinelApprovalGuard::class)->assertCurrent($package->fresh(), $oldAllow);
    }

    public function test_same_timestamp_competing_scan_fails_closed(): void
    {
        [$package, $allow] = $this->packageWithAllowScan('sentinel-tie');
        $this->assertNotNull($allow->created_at);
        $this->createScan($package, 'block', $allow->created_at);
        $package->forceFill(['status' => 'scanned'])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ambiguously concurrent Sentinel scan');
        app(SentinelApprovalGuard::class)->assertCurrent($package->fresh(), $allow);
    }

    public function test_approved_package_digest_mutation_is_rejected(): void
    {
        [$package, $scan, $path] = $this->packageWithAllowScan('sentinel-mutated', true);
        file_put_contents($path, 'mutated-after-approval');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('changed after Sentinel approval');
        app(SentinelApprovalGuard::class)->assertCurrent($package, $scan);
    }

    public function test_failure_reference_never_contains_raw_exception_message(): void
    {
        $secret = 'password=super-secret-value';
        $failure = app(SentinelFailureReference::class)->for(new RuntimeException($secret), 'scan-123');

        $this->assertMatchesRegularExpression('/^SNT-[A-F0-9]{16}$/', $failure['reference']);
        $this->assertStringNotContainsString($secret, $failure['message']);
        $this->assertStringNotContainsString('super-secret-value', $failure['message']);
        $this->assertSame(64, strlen($failure['class_fingerprint']));
    }

    /** @return array{0:QuarantinePackage,1:SecurityScan,2?:string} */
    private function packageWithAllowScan(string $name, bool $includePath = false): array
    {
        $path = storage_path('app/'.$name.'-'.Str::uuid().'.zip');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, 'stable-package-content');
        $this->files[] = $path;
        $sha = hash_file('sha256', $path);
        $this->assertIsString($sha);

        $package = QuarantinePackage::query()->create([
            'id' => (string) Str::uuid(),
            'original_name' => $name.'.zip',
            'stored_name' => Str::uuid().'.zip',
            'path' => $path,
            'sha256' => $sha,
            'size_bytes' => filesize($path),
            'mime_type' => 'application/zip',
            'status' => 'scanned',
            'uploaded_by' => null,
            'scanned_at' => now(),
        ]);
        $scan = $this->createScan($package, 'allow', now());

        return $includePath ? [$package, $scan, $path] : [$package, $scan];
    }

    private function createScan(QuarantinePackage $package, string $decision, \DateTimeInterface $createdAt): SecurityScan
    {
        return SecurityScan::query()->create([
            'id' => (string) Str::uuid(),
            'quarantine_package_id' => $package->id,
            'source_type' => 'archive',
            'source_name' => $package->original_name,
            'source_sha256' => $package->sha256,
            'engine_version' => 'test',
            'status' => 'completed',
            'decision' => $decision,
            'risk_score' => $decision === 'allow' ? 0 : 100,
            'manifest' => ['type' => 'extension'],
            'summary' => [],
            'requested_by' => null,
            'started_at' => $createdAt,
            'completed_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
