<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_authenticate_and_reach_dashboard(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create(['password' => 'password']);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
    }

    public function test_public_health_traffic_does_not_exhaust_the_login_budget(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.60']);
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create(['password' => 'password']);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        for ($request = 0; $request < 6; $request++) {
            $this->get('/health/live')->assertOk();
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_authentication_routes_keep_a_shared_five_attempt_ip_budget(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.61']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', [])->assertUnprocessable();
        }

        $this->postJson('/login', [])->assertStatus(429);
        $this->postJson('/forgot-password', [])->assertStatus(429);
        $this->postJson('/register', [])->assertStatus(429);
        $this->postJson('/reset-password', [])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_standard_user_cannot_enter_admin(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
