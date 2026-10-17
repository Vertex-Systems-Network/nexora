<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_view_own_profile(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->get('/admin/profile')->assertOk();
    }

    public function test_profile_update_persists_and_email_change_clears_verification(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->from('/admin/profile')->put('/admin/profile', [
            'name' => 'Renamed Admin',
            'email' => 'renamed-admin@nexora.test',
            'timezone' => 'Asia/Karachi',
            'locale' => 'en',
        ])->assertRedirect('/admin/profile')->assertSessionHasNoErrors();

        $admin->refresh();
        self::assertSame('Renamed Admin', $admin->name);
        self::assertSame('renamed-admin@nexora.test', $admin->email);
        self::assertNull($admin->email_verified_at);
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'profile.updated']);
    }

    public function test_profile_update_rejects_email_owned_by_another_user(): void
    {
        $admin = $this->administrator();
        User::factory()->create(['email' => 'taken@nexora.test']);

        $this->actingAs($admin)->from('/admin/profile')->put('/admin/profile', [
            'name' => $admin->name,
            'email' => 'taken@nexora.test',
            'timezone' => 'UTC',
            'locale' => 'en',
        ])->assertSessionHasErrors('email');

        self::assertNotSame('taken@nexora.test', $admin->refresh()->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->from('/admin/profile')->put('/admin/profile/password', [
            'current_password' => 'not-the-password',
            'password' => 'N3w-Strong!Passphrase',
            'password_confirmation' => 'N3w-Strong!Passphrase',
        ])->assertSessionHasErrors('current_password');

        self::assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_password_change_rotates_credentials_and_audits(): void
    {
        $admin = $this->administrator();
        $rememberToken = $admin->remember_token;

        $this->actingAs($admin)->from('/admin/profile')->put('/admin/profile/password', [
            'current_password' => 'password',
            'password' => 'N3w-Strong!Passphrase',
            'password_confirmation' => 'N3w-Strong!Passphrase',
        ])->assertRedirect('/admin/profile')->assertSessionHasNoErrors();

        $admin->refresh();
        self::assertTrue(Hash::check('N3w-Strong!Passphrase', $admin->password));
        self::assertNotSame($rememberToken, $admin->remember_token);
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'profile.password_changed']);
    }

    public function test_revoking_other_sessions_keeps_only_current_session(): void
    {
        config(['session.driver' => 'database']);
        $admin = $this->administrator();
        $other = User::factory()->create();
        $table = (string) config('session.table', 'sessions');

        foreach (['stale-a' => $admin->id, 'stale-b' => $admin->id, 'foreign' => $other->id] as $id => $userId) {
            DB::table($table)->insert([
                'id' => $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->actingAs($admin)->from('/admin/profile')->delete('/admin/profile/sessions')
            ->assertRedirect('/admin/profile');

        $this->assertDatabaseMissing($table, ['id' => 'stale-a']);
        $this->assertDatabaseMissing($table, ['id' => 'stale-b']);
        $this->assertDatabaseHas($table, ['id' => 'foreign']);
        $this->assertDatabaseHas('nx_audit_logs', ['user_id' => $admin->id, 'event' => 'profile.sessions_revoked']);
    }

    public function test_session_revocation_is_refused_without_database_driver(): void
    {
        config(['session.driver' => 'array']);

        $this->actingAs($this->administrator())->delete('/admin/profile/sessions')->assertStatus(422);
    }

    public function test_user_without_admin_access_cannot_reach_profile_routes(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->actingAs($user)->get('/admin/profile')->assertForbidden();
        $this->actingAs($user)->put('/admin/profile/password', [
            'current_password' => 'password',
            'password' => 'N3w-Strong!Passphrase',
            'password_confirmation' => 'N3w-Strong!Passphrase',
        ])->assertForbidden();
        self::assertTrue(Hash::check('password', $user->refresh()->password));
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }
}
