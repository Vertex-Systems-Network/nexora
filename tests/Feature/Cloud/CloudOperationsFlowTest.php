<?php

declare(strict_types=1);

namespace Tests\Feature\Cloud;

use App\Models\Role;
use App\Models\RuntimeNode;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CloudOperationsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_super_admin_can_open_operations_and_drain_current_node(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        $this->actingAs($admin)->get('/admin/cloud')->assertOk();
        $this->actingAs($admin)->post('/admin/cloud/node/status', ['status' => 'draining'])->assertRedirect();
        self::assertSame('draining', RuntimeNode::query()->firstOrFail()->status);
        $this->getJson('/health/ready')->assertStatus(503)->assertJsonPath('status', 'not_ready');

        $this->actingAs($admin)->post('/admin/cloud/node/status', ['status' => 'active'])->assertRedirect();
        $this->getJson('/health/ready')->assertOk()->assertJsonPath('status', 'ready');
    }

    public function test_liveness_endpoint_is_minimal_and_public(): void
    {
        $this->getJson('/health/live')->assertOk()->assertJsonPath('service', 'nexora');
    }
}
