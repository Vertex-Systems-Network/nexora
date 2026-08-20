<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RuntimeAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_view_runtime_modules_and_capabilities(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->get('/admin/system/modules')->assertOk();
        $this->actingAs($admin)->get('/admin/system/capabilities')->assertOk();
    }

    public function test_runtime_sync_is_permission_protected_and_audited(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/system/runtime/sync')->assertRedirect();
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'runtime.synchronized']);
    }
}
