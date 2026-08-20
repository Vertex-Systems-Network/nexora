<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_slugs_are_resolved_through_roles(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Reviewer', 'slug' => 'reviewer', 'is_system' => false]);
        $permission = Permission::query()->where('slug', 'audit.view')->firstOrFail();
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        self::assertTrue($user->hasPermission('audit.view'));
        self::assertFalse($user->hasPermission('users.delete'));
    }
}
