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

    public function test_standard_user_cannot_enter_admin(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
