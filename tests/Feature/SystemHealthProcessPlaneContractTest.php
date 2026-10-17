<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemHealthProcessPlaneContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_health_inertia_response_has_a_structured_runtime_process_plane(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        $response = $this->actingAs($admin)->get('/admin/system/health');
        $response->assertOk();
        $page = $response->viewData('page');
        self::assertIsArray($page);
        self::assertSame('Admin/System/Health', $page['component'] ?? null);

        $runtime = $page['props']['runtime'] ?? null;
        self::assertIsArray($runtime);
        foreach (['php', 'laravel', 'environment', 'database'] as $field) {
            self::assertIsString($runtime[$field] ?? null, "Missing runtime scalar: {$field}");
        }

        $plane = $runtime['processPlane'] ?? null;
        self::assertIsArray($plane);
        self::assertContains($plane['status'] ?? null, ['pass', 'fail']);
        self::assertIsArray($plane['required'] ?? null);
        self::assertIsArray($plane['live']['counts'] ?? null);
        self::assertIsArray($plane['checks'] ?? null);
        foreach (['web', 'queue', 'scheduler'] as $role) {
            self::assertIsInt($plane['required'][$role] ?? null);
            self::assertIsInt($plane['live']['counts'][$role] ?? null);
        }
    }
}
