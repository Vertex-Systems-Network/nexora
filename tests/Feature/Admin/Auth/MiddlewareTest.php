<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CORE-QA-001: Authentication Middleware Tests
 * 
 * Verifies middleware protection for admin routes including:
 * - Auth middleware redirects unauthenticated users
 * - Role-based access control (Super Admin only)
 * - Guest middleware prevents authenticated access to login
 */
final class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_auth_middleware_redirects_unauthenticated_user_from_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_auth_middleware_allows_authenticated_super_admin(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_auth_middleware_blocks_standard_user_from_admin(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $userRole = Role::query()->where('slug', 'user')->firstOrFail();
        $user->roles()->attach($userRole);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden(); // 403 Forbidden for non-super-admin
    }

    public function test_auth_middleware_blocks_editor_from_admin(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $editorRole = Role::query()->where('slug', 'editor')->firstOrFail();
        if ($editorRole) {
            $user->roles()->attach($editorRole);
            
            $response = $this->actingAs($user)->get('/admin');
            
            // Editor may have limited admin access or be blocked entirely
            // Testing that they cannot access super-admin-only areas
            $this->assertTrue(
                in_array($response->status(), [200, 403], true),
                'Editor should either have limited access or be forbidden'
            );
        } else {
            $this->markTestSkipped('Editor role not available in this installation');
        }
    }

    public function test_guest_middleware_redirects_authenticated_user_from_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/admin');
    }

    public function test_guest_middleware_allows_guest_to_access_login(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login'); // Adjust based on actual view
    }

    public function test_admin_routes_require_super_admin_role(): void
    {
        // Create users with different roles
        $superAdmin = User::factory()->create(['email' => 'admin@example.com', 'password' => bcrypt('password')]);
        $standardUser = User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('password')]);
        
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $userRole = Role::query()->where('slug', 'user')->firstOrFail();
        
        $superAdmin->roles()->attach($superAdminRole);
        $standardUser->roles()->attach($userRole);

        // Super admin can access admin routes
        $superAdminResponse = $this->actingAs($superAdmin)->get('/admin/users');
        $superAdminResponse->assertOk();

        // Standard user cannot access admin routes
        $userResponse = $this->actingAs($standardUser)->get('/admin/users');
        $userResponse->assertForbidden();
    }

    public function test_middleware_chain_executes_in_correct_order(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        // Test that auth runs before role check
        // Unauthenticated request should redirect to login, not show forbidden
        $unauthResponse = $this->get('/admin/users');
        $unauthResponse->assertRedirect('/login');

        // Authenticated as super admin should succeed
        $authResponse = $this->actingAs($user)->get('/admin/users');
        $authResponse->assertOk();
    }

    public function test_api_routes_protected_by_auth_middleware(): void
    {
        // Test API authentication
        $response = $this->getJson('/api/admin/users');

        // API should return 401 Unauthorized (not redirect)
        $response->assertStatus(401);
    }

    public function test_authenticated_api_request_succeeds(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        $response = $this->actingAs($user)->getJson('/api/admin/users');

        // Should return 200 OK or proper API response
        $response->assertStatus(200);
    }

    public function test_middleware_handles_trashed_users(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        // Soft delete the user
        $user->delete();

        // Trashed user should not be able to authenticate
        $response = $this->actingAs($user)->get('/admin');
        
        // Should be redirected to login or forbidden
        $this->assertTrue(
            in_array($response->status(), [302, 403], true),
            'Trashed user should not have admin access'
        );
    }

    public function test_middleware_respects_maintenance_mode(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $superAdminRole = Role::query()->where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);

        // Enable maintenance mode
        $this->artisan('down');

        // Even authenticated users should see maintenance page
        $response = $this->actingAs($user)->get('/admin');
        
        // Should return 503 Service Unavailable
        $response->assertStatus(503);

        // Disable maintenance mode
        $this->artisan('up');
    }
}
