<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Models\RuntimeLease;
use App\Models\RuntimeNode;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\NodeManager;
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
        config()->set('nexora_cloud.object_storage_disk', 's3');
        config()->set('nexora-ha.required_nodes', 2);

        // Build the peer fixture from the same production heartbeat metadata
        // that real runtime nodes publish. The HA assessor intentionally checks
        // much more than version + timestamp: runtime/environment/activation,
        // data-plane, policy, process, framework and dependency identities must
        // converge across every fresh active node.
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

        $result = app(HaReadinessService::class)->assess();

        self::assertTrue($result['ready'], json_encode($result['checks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        self::assertSame(2, $result['node_count']);
    }
}
