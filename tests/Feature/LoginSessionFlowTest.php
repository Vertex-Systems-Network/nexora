<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class LoginSessionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_login_rotates_the_session_identifier(): void
    {
        $user = $this->superAdmin();
        $this->startSession();
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        self::assertNotSame($before, session()->getId());
    }

    public function test_remember_me_issues_recaller_cookie_only_when_requested(): void
    {
        $user = $this->superAdmin();
        $recaller = Auth::guard('web')->getRecallerName();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin')
            ->assertCookieMissing($recaller);
        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->assertRedirect('/admin')
            ->assertCookie($recaller);
        self::assertNotNull($user->refresh()->remember_token);
    }

    public function test_failed_login_does_not_reveal_whether_account_exists(): void
    {
        $user = $this->superAdmin();

        $known = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $unknown = $this->from('/login')->post('/login', ['email' => 'nobody@nexora.test', 'password' => 'wrong-password']);

        $known->assertSessionHasErrors('email');
        $unknown->assertSessionHasErrors('email');
        self::assertSame(session('errors')->first('email'), __('These credentials do not match our records.'));
        $this->assertGuest();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['password' => 'password', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));

        return $user;
    }
}
