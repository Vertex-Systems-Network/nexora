<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExtensionsAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_open_the_extensions_workspace(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->get('/admin/extensions')->assertOk();
    }

    public function test_non_authenticated_user_cannot_open_the_extensions_workspace(): void
    {
        $this->get('/admin/extensions')->assertRedirect('/login');
    }
}
