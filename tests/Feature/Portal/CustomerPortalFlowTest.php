<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Models\CommerceCustomer;
use App\Models\EnterpriseOrganization;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerPortalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_standard_user_login_goes_to_customer_portal_instead_of_admin(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->roles()->attach(Role::query()->where('slug', 'user')->value('id'));

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.86'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_portal_exposes_only_current_users_linked_customer_and_memberships(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $other = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $default = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();

        app(TenantContext::class)->runWith($default, function () use ($user, $other): void {
            $customer = CommerceCustomer::query()->create([
                'user_id' => $user->id,
                'name' => 'Portal Customer',
                'email' => 'portal-customer@example.test',
            ]);
            CommerceCustomer::query()->create([
                'user_id' => $other->id,
                'name' => 'Other Customer',
                'email' => 'other-customer@example.test',
            ]);

            $plan = MembershipPlan::query()->create([
                'name' => 'Portal Plan',
                'slug' => 'portal-plan',
                'status' => 'active',
                'metadata' => [],
            ]);
            $otherPlan = MembershipPlan::query()->create([
                'name' => 'Other Plan',
                'slug' => 'other-plan',
                'status' => 'active',
                'metadata' => [],
            ]);

            Membership::query()->create([
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'commerce_customer_id' => $customer->id,
                'status' => 'active',
                'started_at' => now(),
                'metadata' => [],
            ]);
            Membership::query()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $other->id,
                'status' => 'active',
                'started_at' => now(),
                'metadata' => [],
            ]);
        });

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('customer.email', 'portal-customer@example.test')
                ->has('memberships', 1)
                ->where('memberships.0.plan', 'Portal Plan')
                ->has('orders', 0)
                ->has('invoices', 0)
                ->has('subscriptions', 0)
            );
    }

    public function test_membership_only_user_gets_safe_empty_commerce_state(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $default = EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();

        app(TenantContext::class)->runWith($default, function () use ($user): void {
            $plan = MembershipPlan::query()->create([
                'name' => 'Access Only',
                'slug' => 'access-only',
                'status' => 'active',
                'metadata' => [],
            ]);
            Membership::query()->create([
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'status' => 'active',
                'started_at' => now(),
                'metadata' => [],
            ]);
        });

        $this->actingAs($user)->get('/account')->assertInertia(fn (Assert $page) => $page
            ->where('customer', null)
            ->has('memberships', 1)
            ->has('orders', 0)
            ->has('invoices', 0)
            ->has('subscriptions', 0)
        );
    }
}
