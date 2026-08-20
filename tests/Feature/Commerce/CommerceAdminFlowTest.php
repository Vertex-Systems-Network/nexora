<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommerceAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_open_commerce_workspaces(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));
        foreach(['/admin/commerce','/admin/commerce/products','/admin/commerce/customers','/admin/commerce/orders','/admin/commerce/billing','/admin/commerce/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guest_cannot_open_commerce_admin(): void
    {
        $this->get('/admin/commerce')->assertRedirect('/login');
    }
}
