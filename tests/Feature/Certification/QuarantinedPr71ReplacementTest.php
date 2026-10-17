<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Source regressions only; this suite does not certify a deployed target. */
final class QuarantinedPr71ReplacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    private function administrator(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->sole()->id);

        return $user;
    }

    public function test_guests_cannot_read_dashboard_or_module_inventory(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/system/modules')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unverified_administrator_must_verify_before_admin_entry(): void
    {
        $user = $this->administrator(['email_verified_at' => null]);

        $this->actingAs($user)->get('/admin')->assertRedirect('/verify-email');
        $this->get('/admin/system/modules')->assertRedirect('/verify-email');
    }

    public function test_suspended_administrator_is_denied_even_with_super_admin_role(): void
    {
        $user = $this->administrator(['status' => 'suspended']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->get('/admin/system/modules')->assertForbidden();
    }

    public function test_wrong_password_does_not_authenticate_and_creates_failure_audit(): void
    {
        $user = $this->administrator(['password' => 'valid-test-password']);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong-test-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'auth.login_failed']);
    }

    public function test_suspended_account_cannot_authenticate_with_correct_password(): void
    {
        $user = $this->administrator(['status' => 'suspended', 'password' => 'valid-test-password']);

        $this->postJson('/login', ['email' => $user->email, 'password' => 'valid-test-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'auth.login_blocked']);
    }

    public function test_logout_revokes_current_authentication_and_admin_access(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/login');
        $this->assertDatabaseHas('nx_audit_logs', ['event' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_dashboard_reports_actual_user_and_registered_module_counts(): void
    {
        $user = $this->administrator();
        $users = User::query()->count();
        $modules = count(app(ModuleRegistryContract::class)->manifests());
        self::assertGreaterThan(0, $modules);

        $this->actingAs($user)->get('/admin')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard')
                ->where('summary.users', $users)
                ->where('summary.modules', $modules)
                ->where('database.driver', (string) config('database.connections.'.config('database.default').'.driver'))
                ->where('database.connected', true));
    }

    public function test_module_inventory_reports_manifest_hash_mismatch(): void
    {
        $user = $this->administrator();
        $manifests = app(ModuleRegistryContract::class)->manifests();
        self::assertNotEmpty($manifests);
        $identifier = array_key_first($manifests);
        $record = Module::query()->where('identifier', $identifier)->sole();
        $record->update(['manifest_hash' => str_repeat('0', 64)]);

        $this->actingAs($user)->get('/admin/system/modules')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/System/Modules')
                ->where('summary.registered', count($manifests))
                ->where('canSync', true)
                ->where('modules', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['identifier'] === $identifier && $row['synced'] === false
                        && $row['manifestHash'] === $manifests[$identifier]->hash()
                )));
    }
}
