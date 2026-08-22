<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdentityAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_user_without_admin_permission_cannot_enter_admin(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administrator_can_view_users_and_roles(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
    }

    public function test_custom_role_permissions_are_enforced_by_backend(): void
    {
        $role = Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor', 'is_system' => false]);
        $role->permissions()->sync(Permission::query()->whereIn('slug', ['admin.access', 'audit.view'])->pluck('id'));
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/audit')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->assertDatabaseHas('nx_audit_logs', [
            'user_id' => $user->id,
            'event' => 'authorization.denied',
        ]);
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $role = Role::query()->where('slug', 'user')->firstOrFail();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Test User', 'email' => 'test-user@nexora.test', 'status' => 'active',
            'timezone' => 'UTC', 'locale' => 'en', 'verified' => true, 'roles' => [$role->id],
            'password' => 'Password!123', 'password_confirmation' => 'Password!123',
        ])->assertRedirect('/admin/users');

        $user = User::query()->where('email', 'test-user@nexora.test')->firstOrFail();
        self::assertTrue($user->hasRole('user'));
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'user.created', 'subject_id' => (string) $user->id]);
    }


    public function test_final_super_admin_cannot_be_demoted_or_suspended(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $superRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $admin->roles()->attach($superRole);
        $userRole = Role::query()->where('slug', 'user')->firstOrFail();

        $payload = [
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'verified' => true,
            'roles' => [$userRole->id],
            'password' => '',
            'password_confirmation' => '',
        ];

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", $payload)->assertStatus(422);
        self::assertTrue($admin->fresh()->hasRole('super-admin'));

        $payload['roles'] = [$superRole->id];
        $payload['status'] = 'suspended';
        $this->actingAs($admin)->put("/admin/users/{$admin->id}", $payload)->assertStatus(422);
        self::assertSame('active', $admin->fresh()->status);
    }

    public function test_bulk_status_update_skips_current_and_final_super_admin(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        $other = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($admin)->put('/admin/users/bulk', [
            'ids' => [$admin->id, $other->id],
            'action' => 'suspend',
        ])->assertRedirect();

        self::assertSame('active', $admin->fresh()->status);
        self::assertSame('suspended', $other->fresh()->status);
    }

    public function test_saved_views_are_owned_by_the_authenticated_admin(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $otherAdmin = User::factory()->create(['email_verified_at' => now()]);
        $otherAdmin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        // Saved views use the same fetch-based JSON path as the Admin client.
        // Production fetch requests carry the active deployment generation so
        // the runtime stale-client fence can reject genuinely old clients.
        $this->withHeader(
            'X-Nexora-Deployment-Generation',
            app(RuntimeDeploymentIdentity::class)->generation(),
        );

        $response = $this->actingAs($admin)->postJson('/admin/saved-views', [
            'scope' => 'admin.users',
            'name' => 'Suspended users',
            'state' => ['status' => 'suspended'],
        ])->assertCreated();

        $viewId = $response->json('view.id');
        $this->actingAs($otherAdmin)->deleteJson("/admin/saved-views/{$viewId}")->assertNotFound();
        $this->assertDatabaseHas('nx_saved_views', ['id' => $viewId, 'user_id' => $admin->id]);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertStatus(422);
        $this->assertDatabaseHas('nx_roles', ['id' => $role->id]);
    }
}
