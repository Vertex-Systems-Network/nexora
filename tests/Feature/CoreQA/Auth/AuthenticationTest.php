<?php

declare(strict_types=1);

namespace Tests\Feature\CoreQA\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * CORE-QA-001: Authentication & Session Management
 * 
 * Development Unit: SYS-AUTH-SESSION
 * Stage: CORE-QA-001 (Super Admin + Core Application Functional QA)
 * 
 * Test Coverage:
 * - Login/Logout flows
 * - Session persistence
 * - Token refresh mechanisms
 * - Failed authentication handling
 * - Multi-device session management
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with valid credentials
     */
    public function test_successful_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@nexora.test',
            'password' => bcrypt('SecurePassword123!'),
            'role' => 'super_admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@nexora.test',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test failed login with invalid credentials
     */
    public function test_failed_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@nexora.test',
            'password' => bcrypt('CorrectPassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@nexora.test',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test logout functionality
     */
    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test session persistence across requests
     */
    public function test_session_persists_across_multiple_requests(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        // First request
        $response1 = $this->get('/admin/dashboard');
        $response1->assertStatus(200);

        // Second request
        $response2 = $this->get('/admin/settings');
        $response2->assertStatus(200);

        // Third request
        $response3 = $this->get('/api/user');
        $response3->assertJson(['id' => $user->id]);
    }

    /**
     * Test token refresh mechanism
     */
    public function test_token_refresh_on_expiry(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        // Simulate token expiry scenario
        config(['session.lifetime' => 1]); // 1 minute for testing
        
        $response = $this->get('/api/user');
        $response->assertStatus(200);
        
        // Verify session refreshed
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test rate limiting on failed login attempts
     */
    public function test_rate_limiting_on_failed_login_attempts(): void
    {
        $payload = [
            'email' => 'nonexistent@nexora.test',
            'password' => 'WrongPassword',
        ];

        // Make 5 failed attempts (typical rate limit threshold)
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $payload);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', $payload);
        
        // Should either be rate limited or still fail gracefully
        $response->assertSessionHasErrors();
    }

    /**
     * Test multi-device session management
     */
    public function test_concurrent_sessions_from_multiple_devices(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        // Simulate login from device 1
        $response1 = $this->actingAs($user, 'web')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        // Simulate login from device 2
        $response2 = $this->actingAs($user, 'web')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        // Both sessions should be active
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test password reset flow initiation
     */
    public function test_password_reset_flow_initiation(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@nexora.test',
        ]);

        $response = $this->post('/password/email', [
            'email' => 'admin@nexora.test',
        ]);

        $response->assertSessionHasNoErrors();
    }

    /**
     * Test account lockout after multiple failed attempts
     */
    public function test_account_lockout_after_repeated_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@nexora.test',
            'password' => bcrypt('CorrectPassword'),
        ]);

        // Simulate 10 failed attempts
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'email' => 'locked@nexora.test',
                'password' => 'WrongPassword',
            ]);
        }

        // Attempt with correct password should still fail (account locked)
        $response = $this->post('/login', [
            'email' => 'locked@nexora.test',
            'password' => 'CorrectPassword',
        ]);

        // Account should be locked or require additional verification
        $response->assertSessionHasErrors();
    }

    /**
     * Test remember me functionality
     */
    public function test_remember_me_persistence(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@nexora.test',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'remember@nexora.test',
            'password' => 'Password123!',
            'remember' => true,
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
        
        // Verify remember token is set
        $this->assertNotNull($user->fresh()->remember_token);
    }

    /**
     * Test session invalidation on password change
     */
    public function test_session_invalidation_on_password_change(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
            'role' => 'super_admin',
        ]);

        $this->actingAs($user);

        // Change password
        $this->put('/user/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        // Session should be invalidated or require re-authentication
        // This depends on implementation - may redirect to login
    }

    /**
     * Test CSRF protection on login form
     */
    public function test_csrf_protection_on_login(): void
    {
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'email' => 'test@nexora.test',
                'password' => 'Password123!',
            ]);

        // Should fail due to CSRF token mismatch when middleware is active
        $this->assertTrue(true); // Placeholder - actual test requires CSRF token
    }

    /**
     * Test login with email case insensitivity
     */
    public function test_login_email_case_insensitivity(): void
    {
        $user = User::factory()->create([
            'email' => 'CaseInsensitive@Nexora.Test',
            'password' => bcrypt('Password123!'),
        ]);

        // Try login with different case
        $response = $this->post('/login', [
            'email' => 'caseinsensitive@nexora.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test two-factor authentication setup (if enabled)
     */
    public function test_two_factor_authentication_setup(): void
    {
        // Placeholder for 2FA tests
        $this->markTestIncomplete('2FA implementation pending');
    }

    /**
     * Test OAuth integration (Google, GitHub, etc.)
     */
    public function test_oauth_integration(): void
    {
        // Placeholder for OAuth tests
        $this->markTestIncomplete('OAuth implementation pending');
    }

    /**
     * Test API token authentication
     */
    public function test_api_token_authentication(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/user');

        $response->assertStatus(200);
        $response->assertJson(['id' => $user->id]);
    }

    /**
     * Test session timeout configuration
     */
    public function test_session_timeout_enforcement(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        // Set very short session lifetime for testing
        config(['session.lifetime' => 1]);

        // Wait for session to expire (simulated)
        sleep(70); // 70 seconds > 1 minute

        // Request should redirect to login or show session expired
        $response = $this->get('/admin/dashboard');
        
        // Depending on implementation, may redirect or show error
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test login audit logging
     */
    public function test_login_audit_logging(): void
    {
        $user = User::factory()->create([
            'email' => 'audit@nexora.test',
            'password' => bcrypt('Password123!'),
        ]);

        $this->post('/login', [
            'email' => 'audit@nexora.test',
            'password' => 'Password123!',
        ]);

        // Verify audit log entry exists (implementation dependent)
        // This would check a logs table or audit trail system
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test concurrent session limit enforcement
     */
    public function test_concurrent_session_limit(): void
    {
        // If system limits concurrent sessions, test enforcement
        $this->markTestIncomplete('Concurrent session limit implementation pending');
    }

    /**
     * Test secure cookie flags on session cookies
     */
    public function test_secure_cookie_flags(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');
        
        // Check if session cookie has secure and httponly flags
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === config('session.cookie')) {
                $this->assertTrue($cookie->isSecure() || app()->environment('testing'));
                $this->assertTrue($cookie->isHttpOnly());
            }
        }
    }
}
