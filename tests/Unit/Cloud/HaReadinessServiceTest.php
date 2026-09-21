<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HaReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_strict_ha_readiness_requires_shared_runtime_and_multiple_matching_nodes(): void
    {
        config()->set('cache.default', 'database');
        config()->set('session.driver', 'database');
        config()->set('queue.default', 'database');

        config()->set('nexora_cloud.node_id', 'node-a');
        config()->set('nexora_cloud.object_storage_disk', 's3');
        config()->set('nexora-storage-runtime.object_disk', 's3');
        config()->set('nexora-storage-runtime.backup_disk', 's3');
        config()->set('nexora-ha.required_nodes', 2);

        // Keep every strict runtime/resource check enabled while binding this
        // unit fixture to deterministic thresholds instead of the runner host's
        // PHP upload/body limits, filesystem capacity, or worker defaults.
        config()->set('nexora-resource-runtime.require_deep_capacity_for_ha', true);
        foreach ([
            'minimum_memory_headroom_bytes',
            'minimum_queue_memory_headroom_bytes',
            'minimum_temp_free_bytes',
            'minimum_storage_free_bytes',
            'minimum_transfer_free_bytes',
            'minimum_bootstrap_free_bytes',
            'minimum_backup_staging_free_bytes',
            'minimum_open_files_soft',
        ] as $key) {
            config()->set('nexora-resource-runtime.'.$key, 1);
        }

        config()->set('nexora-runtime.http.max_body_bytes', 1);
        config()->set('nexora-runtime.php.minimum_memory_bytes', 1);
        config()->set('nexora-runtime.php.minimum_post_bytes', 1);
        config()->set('nexora-runtime.php.minimum_upload_bytes', 1);
        config()->set('nexora-runtime.php.minimum_execution_seconds', 1);
        config()->set('nexora-runtime.php.minimum_input_seconds', 1);
        config()->set('nexora-runtime.php.minimum_input_vars', 1);
        config()->set('nexora-runtime.php.minimum_file_uploads', 1);
        config()->set('nexora-runtime.queue.max_job_timeout_seconds', 1);
        config()->set('nexora-runtime.queue.retry_after_margin_seconds', 1);
        config()->set('nexora-runtime.queue.worker_timeout_seconds', 1);
        config()->set('nexora-runtime.queue.worker_max_time_seconds', 2);
        config()->set('nexora-runtime.queue.worker_restart_memory_mb', 1024);
        foreach (['database', 'redis', 'beanstalkd'] as $connection) {
            config()->set('queue.connections.'.$connection.'.retry_after', 10);
        }

        $reviewPath = base_path(ReviewedDependencyState::REVIEW_PATH);
        $reviewDirectory = dirname($reviewPath);
        $existingReview = is_file($reviewPath) ? file_get_contents($reviewPath) : null;

        if (! is_dir($reviewDirectory)) {
            self::assertTrue(mkdir($reviewDirectory, 0700, true) || is_dir($reviewDirectory));
        }

        $dependencies = app(ReviewedDependencyState::class);
        $review = [
            'status' => 'reviewed',
            ...$dependencies->currentHashes(),
            'laravel_framework_locked_version' => $dependencies->lockedLaravelVersion(),
        ];
        file_put_contents(
            $reviewPath,
            json_encode($review, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
        );

        try {
            self::assertSame('pass', app(ReviewedDependencyState::class)->inspect()['status']);

            $resource = app(RuntimeResourceEnvelopeIdentity::class)->current(true);
            self::assertSame(
                'pass',
                $resource['status'],
                json_encode(
                    [
                        'limits_status' => $resource['limits_status'] ?? null,
                        'limits_checks' => $resource['limits_checks'] ?? [],
                        'deep' => $resource['deep'] ?? null,
                    ],
                    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
            );

            $leases = app(RuntimeLeaseManager::class);
            $processes = app(RuntimeProcessPlane::class);
            $processFingerprint = $processes->fingerprintValue();
            $leaseTtl = 180;

            foreach ([
                ['web', 'node-a'],
                ['web', 'node-b'],
                ['queue', 'node-a'],
                ['queue', 'node-b'],
                ['scheduler', 'node-a'],
            ] as [$role, $owner]) {
                self::assertTrue($leases->acquireOrRenew(
                    'runtime-process:'.$role.':'.$owner,
                    $owner,
                    $leaseTtl,
                    [
                        'kind' => 'runtime-process',
                        'role' => $role,
                        'platform_version' => (string) config('nexora.version'),
                        'process_policy_fingerprint' => $processFingerprint,
                        'sapi' => PHP_SAPI,
                    ],
                ));
            }

            $nodeA = app(NodeManager::class)->heartbeat();
            self::assertNotNull($nodeA);
            $nodeA->forceFill(['status' => 'active'])->save();
            $nodeA->refresh();

            RuntimeNode::query()->create([
                'id' => (string) Str::uuid(),
                'node_key' => 'node-b',
                'hostname' => 'node-b.test',
                'status' => 'active',
                'role' => $nodeA->role,
                'version' => $nodeA->version,
                'environment' => $nodeA->environment,
                'capabilities' => $nodeA->capabilities,
                'metadata' => $nodeA->metadata,
                'last_heartbeat_at' => now(),
            ]);

            self::assertTrue($leases->acquireOrRenew(
                'scheduler-leader',
                'node-a',
                $leaseTtl,
                ['kind' => 'scheduler-leader'],
            ));

            $result = app(HaReadinessService::class)->assess();

            self::assertTrue(
                $result['ready'],
                json_encode($result['checks'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
            self::assertSame(2, $result['node_count']);
        } finally {
            if (is_string($existingReview)) {
                file_put_contents($reviewPath, $existingReview);
            } else {
                @unlink($reviewPath);
            }
        }
    }
}
