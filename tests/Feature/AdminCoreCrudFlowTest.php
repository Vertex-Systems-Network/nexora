<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminCoreCrudFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_admin_can_update_user_profile_status_and_roles(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach($this->roleId('user'));
        $editor = Role::query()->create(['name' => 'Editor QA', 'slug' => 'editor-qa', 'is_system' => false]);

        $this->actingAs($admin)->put('/admin/users/'.$user->id, [
            'name' => 'Updated Name', 'email' => 'updated-user@nexora.test', 'status' => 'suspended',
            'timezone' => 'UTC', 'locale' => 'en', 'verified' => false, 'roles' => [$editor->id],
        ])->assertRedirect('/admin/users')->assertSessionHasNoErrors();

        $user->refresh();
        self::assertSame('Updated Name', $user->name);
        self::assertSame('updated-user@nexora.test', $user->email);
        self::assertSame('suspended', $user->status);
        self::assertNull($user->email_verified_at);
        self::assertTrue($user->hasRole('editor-qa'));
        self::assertFalse($user->hasRole('user'));
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'user.updated', 'subject_id' => (string) $user->id]);
    }

    public function test_created_user_verification_follows_the_verified_flag(): void
    {
        $admin = $this->administrator();
        foreach (['verified-user@nexora.test' => true, 'unverified-user@nexora.test' => false] as $email => $verified) {
            $this->actingAs($admin)->post('/admin/users', [
                'name' => 'Flag User', 'email' => $email, 'status' => 'active',
                'timezone' => 'UTC', 'locale' => 'en', 'verified' => $verified, 'roles' => [$this->roleId('user')],
                'password' => 'Password!123', 'password_confirmation' => 'Password!123',
            ])->assertRedirect('/admin/users')->assertSessionHasNoErrors();
        }

        self::assertNotNull(User::query()->where('email', 'verified-user@nexora.test')->value('email_verified_at'));
        self::assertNull(User::query()->where('email', 'unverified-user@nexora.test')->value('email_verified_at'));
    }

    public function test_admin_cannot_suspend_own_account(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'status' => 'suspended',
            'timezone' => 'UTC', 'locale' => 'en', 'verified' => true, 'roles' => [$this->roleId('administrator')],
        ])->assertStatus(422);

        self::assertNotSame('suspended', $admin->refresh()->status);
    }

    public function test_admin_can_delete_another_user_but_not_self(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create();

        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($admin)->delete('/admin/users/'.$user->id)->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'user.deleted', 'subject_id' => (string) $user->id]);
    }

    public function test_final_super_admin_cannot_be_deleted(): void
    {
        $superAdmin = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->first()
            ?? tap(User::factory()->create(), fn (User $u) => $u->roles()->attach($this->roleId('super-admin')));
        self::assertSame(1, User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->count());

        $this->actingAs($this->administrator())->delete('/admin/users/'.$superAdmin->id)->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_admin_can_update_custom_role_and_its_permissions(): void
    {
        $role = Role::query()->create(['name' => 'Reviewer', 'slug' => 'reviewer', 'is_system' => false]);
        $permissions = Permission::query()->whereIn('slug', ['admin.access', 'audit.view'])->pluck('id')->all();

        $this->actingAs($this->administrator())->put('/admin/roles/'.$role->id, [
            'name' => 'Senior Reviewer', 'slug' => 'senior-reviewer', 'description' => 'QA',
            'permissions' => $permissions,
        ])->assertRedirect('/admin/roles')->assertSessionHasNoErrors();

        $role->refresh();
        self::assertSame('Senior Reviewer', $role->name);
        self::assertSame('senior-reviewer', $role->slug);
        self::assertEqualsCanonicalizing($permissions, $role->permissions()->pluck('nx_permissions.id')->all());
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'role.updated', 'subject_id' => (string) $role->id]);
    }

    public function test_system_role_slug_is_immutable_on_update(): void
    {
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        $this->actingAs($this->administrator())->put('/admin/roles/'.$role->id, [
            'name' => 'Administrator', 'slug' => 'renamed-admin',
            'permissions' => $role->permissions()->pluck('nx_permissions.id')->all(),
        ])->assertRedirect('/admin/roles');

        self::assertSame('administrator', $role->refresh()->slug);
    }

    public function test_role_with_assigned_users_cannot_be_deleted_until_empty(): void
    {
        $admin = $this->administrator();
        $role = Role::query()->create(['name' => 'Temporary', 'slug' => 'temporary', 'is_system' => false]);
        $member = User::factory()->create();
        $member->roles()->attach($role);

        $this->actingAs($admin)->delete('/admin/roles/'.$role->id)->assertStatus(422);
        $this->assertDatabaseHas('nx_roles', ['id' => $role->id]);

        $member->roles()->detach($role);
        $this->actingAs($admin)->delete('/admin/roles/'.$role->id)->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('nx_roles', ['id' => $role->id]);
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'role.deleted']);
    }

    public function test_media_in_use_is_protected_from_permanent_deletion(): void
    {
        $admin = $this->administrator();
        $asset = $this->uploadAsset($admin);
        MediaUsage::query()->create([
            'asset_id' => $asset->id, 'resource_type' => 'page', 'resource_id' => 1, 'slot' => 'hero',
        ]);

        $this->actingAs($admin)->delete('/admin/media/'.$asset->id.'/force')->assertSessionHasErrors('media');

        self::assertNotNull(MediaAsset::withTrashed()->find($asset->id));
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_unused_media_can_be_permanently_deleted(): void
    {
        $admin = $this->administrator();
        $asset = $this->uploadAsset($admin);
        $this->actingAs($admin)->delete('/admin/media/'.$asset->id)->assertSessionHasNoErrors();

        $this->actingAs($admin)->delete('/admin/media/'.$asset->id.'/force')->assertSessionHasNoErrors();

        self::assertNull(MediaAsset::withTrashed()->find($asset->id));
        Storage::disk('public')->assertMissing($asset->path);
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'media.asset.deleted', 'subject_id' => (string) $asset->id]);
    }

    private function uploadAsset(User $admin): MediaAsset
    {
        $header = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
        $this->actingAs($admin)->post('/admin/media/upload', [
            'file' => UploadedFile::fake()->createWithContent('guide.pdf', str_pad($header, 64 * 1024, "\n")),
            'title' => 'QA guide', 'alt_text' => '', 'caption' => 'QA document',
        ])->assertSessionHasNoErrors();

        return MediaAsset::query()->latest('id')->firstOrFail();
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach($this->roleId('administrator'));

        return $admin;
    }

    private function roleId(string $slug): int
    {
        return (int) Role::query()->where('slug', $slug)->value('id');
    }
}
