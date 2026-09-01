<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Models\RuntimeLease;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Foundation\Runtime\ReviewedDependencyState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
        config()->set('nexora_cloud.object_storage_disk', 's3');
        config()->set('nexora-ha.required_nodes', 2);

        // The hosted test runner intentionally has conservative PHP/disk limits.
        // This unit verifies HA identity/quorum behavior, so admit the observed
        // test host while retaining the same production checks and code paths.
        foreach ([
            'nexora-runtime.php.minimum_memory_bytes',
            'nexora-runtime.php.minimum_post_bytes',
            'nexora-runtime.php.minimum_upload_bytes',
            'nexora-runtime.php.minimum_execution_seconds',
            'nexora-runtime.php.minimum_input_seconds',
            'nexora-runtime.php.minimum_input_vars',
            'nexora-runtime.php.minimum_file_uploads',
            'nexora-runtime.http.max_body_bytes',
            'nexora-resource-runtime.minimum_memory_headroom_bytes',
            'nexora-resource-runtime.minimum_queue_memory_headroom_bytes',
            'nexora-resource-runtime.minimum_temp_free_bytes',
            'nexora-resource-runtime.minimum_storage_free_bytes',
            'nexora-resource-runtime.minimum_transfer_free_bytes',
            'nexora-resource-runtime.minimum_bootstrap_free_bytes',
            'nexora-resource-runtime.minimum_backup_staging_free_bytes',
            'nexora-resource-runtime.minimum_open_files_soft',
        ] as $key) {
            config()->set($key, 0);
        }
        // Keep the strict transfer policy internally consistent with the
        // intentionally-zero HTTP ceiling used by this isolated HA fixture.
        config()->set('nexora-transfers.media.max_upload_bytes', 0);
        config()->set('nexora-runtime.queue.max_job_timeout_seconds', 1);
        config()->set('nexora-runtime.queue.retry_after_margin_seconds', 0);
        config()->set('nexora-runtime.queue.worker_timeout_seconds', 1);
        config()->set('nexora-runtime.queue.worker_max_time_seconds', 2);

        // Object storage was already made shared above; make the backup role use
        // the same shared driver so strict DR/HA readiness remains satisfied.
        config()->set('nexora-storage-runtime.object_disk', 's3');
        config()->set('nexora-storage-runtime.backup_disk', 's3');

        $dependencies = app(ReviewedDependencyState::class);
        $reviewPath = base_path(ReviewedDependencyState::REVIEW_PATH);
        File::ensureDirectoryExists(dirname($reviewPath));
        @unlink($reviewPath);

        $hashes = $dependencies->currentHashes();
        $lockedLaravel = $dependencies->lockedLaravelVersion();
        foreach ($hashes as $hash) {
            self::assertIsString($hash, 'Development QA must materialize dependency lock identity before HA tests run.');
        }
        self::assertIsString($lockedLaravel);

        File::put($reviewPath, json_encode([
            'status' => 'reviewed',
            ...$hashes,
            'laravel_framework_locked_version' => $lockedLaravel,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        try {
            // Build the peer fixture from the same production heartbeat metadata
            // that real runtime nodes publish. The HA assessor intentionally checks
            // runtime/environment/activation, data-plane, policy, process,
            // framework and dependency identities across every fresh active node.
            $template = app(NodeManager::class)->heartbeat('application');
            self::assertNotNull($template);

            $peer = [
                'hostname' => $template->hostname,
                'role' => $template->role,
                'version' => $template->version,
                'environment' => $template->environment,
                'status' => 'active',
                'capabilities' => $template->capabilities,
                'metadata' => $template->metadata,
                'last_heartbeat_at' => now(),
            ];

            $template->delete();

            foreach (['node-a', 'node-b'] as $key) {
                RuntimeNode::query()->create([
                    'id' => (string) Str::uuid(),
                    'node_key' => $key,
                    ...$peer,
                ]);
            }

            RuntimeLease::query()->create([
                'id' => (string) Str::uuid(),
                'name' => 'scheduler-leader',
                'owner_node_key' => 'node-a',
                'expires_at' => now()->addMinute(),
                'heartbeat_at' => now(),
            ]);

            foreach ([
                ['web', 'node-a'],
                ['web', 'node-b'],
                ['queue', 'node-a'],
                ['queue', 'node-b'],
                ['scheduler', 'node-a'],
            ] as [$role, $owner]) {
                RuntimeLease::query()->create([
                    'id' => (string) Str::uuid(),
                    'name' => "runtime-process:{$role}:{$owner}",
                    'owner_node_key' => $owner,
                    'expires_at' => now()->addMinute(),
                    'heartbeat_at' => now(),
                    'metadata' => ['kind' => 'runtime-process', 'role' => $role],
                ]);
            }

            $result = app(HaReadinessService::class)->assess();

            self::assertTrue($result['ready'], json_encode($result['checks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            self::assertSame(2, $result['node_count']);
        } finally {
            @unlink($reviewPath);
        }
    }
}
