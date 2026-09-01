<?php

declare(strict_types=1);

namespace Tests\Feature\Automation;

use App\Models\AdminNotification;
use App\Models\AutomationEvent;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Automation\Services\WorkflowActionExecutor;
use App\Nexora\Enterprise\Services\TenantContext;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class AutomationTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_automation_admin_rejects_notification_target_from_another_tenant(): void
    {
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Other automation tenant', 'other-automation-tenant');
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['name' => 'Other Automation Member', 'email' => 'other-automation@example.test']);
        $admin->roles()->attach(Role::query()->where('slug', 'super-admin')->value('id'));
        $this->addMember($primary, $admin, 'owner');
        $this->addMember($other, $otherUser);
        app(TenantContext::class)->set($primary);

        $this->actingAs($admin)->get('/admin/automation')
            ->assertOk()
            ->assertDontSee('other-automation@example.test');

        $this->actingAs($admin)->post('/admin/automation', [
            'name' => 'Cross tenant notification',
            'slug' => 'cross-tenant-notification',
            'status' => 'active',
            'trigger_key' => 'manual',
            'trigger_config' => [],
            'conditions' => [],
            'actions' => [[
                'key' => 'notify',
                'type' => 'admin.notification',
                'config' => [
                    'user_id' => $otherUser->id,
                    'title' => 'Should not be accepted',
                    'message' => 'Cross tenant notification',
                ],
            ]],
        ])->assertSessionHasErrors('actions.0.config.user_id');

        self::assertFalse(Workflow::query()->where('slug', 'cross-tenant-notification')->exists());
    }

    public function test_event_idempotency_and_workflow_slug_are_scoped_per_tenant(): void
    {
        Queue::fake();
        $primary = $this->defaultOrganization();
        $other = $this->createOrganization('Second automation tenant', 'second-automation-tenant');
        $context = app(TenantContext::class);

        $context->set($primary);
        Workflow::query()->create($this->workflowDefinition('Shared workflow', 'shared-workflow', 'tenant.event'));
        $primaryEvent = app(AutomationEventBusContract::class)->emit('tenant.event', ['tenant' => 'primary'], idempotencyKey: 'shared-event-key');

        $context->set($other);
        Workflow::query()->create($this->workflowDefinition('Shared workflow', 'shared-workflow', 'tenant.event'));
        $otherEvent = app(AutomationEventBusContract::class)->emit('tenant.event', ['tenant' => 'other'], idempotencyKey: 'shared-event-key');

        self::assertNotNull($primaryEvent);
        self::assertNotNull($otherEvent);
        self::assertNotSame($primaryEvent->tenant_id, $otherEvent->tenant_id);
        self::assertSame(2, Workflow::query()->withoutGlobalScope('nexora_tenant')->where('slug', 'shared-workflow')->count());
        self::assertSame(2, AutomationEvent::query()->withoutGlobalScope('nexora_tenant')->where('idempotency_key', 'shared-event-key')->count());
    }

    public function test_notification_action_rechecks_membership_at_execution_time(): void
    {
        $primary = $this->defaultOrganization();
        $user = User::factory()->create(['name' => 'Automation Recipient', 'email' => 'automation-recipient@example.test']);
        $membership = $this->addMember($primary, $user);
        app(TenantContext::class)->set($primary);

        $workflow = Workflow::query()->create($this->workflowDefinition('Runtime membership', 'runtime-membership', 'manual'));
        $run = WorkflowRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'context' => ['message' => 'hello'],
        ]);

        $membership->forceFill(['status' => 'inactive'])->save();

        try {
            app(WorkflowActionExecutor::class)->execute($run, [
                'key' => 'notify',
                'type' => 'admin.notification',
                'config' => [
                    'user_id' => $user->id,
                    'title' => 'Membership changed',
                    'message' => '{{ message }}',
                ],
            ]);
            self::fail('Expected stale automation notification membership to fail closed.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('not an active member', $exception->getMessage());
        }

        self::assertSame(0, AdminNotification::query()->where('user_id', $user->id)->count());
    }

    /** @return array<string,mixed> */
    private function workflowDefinition(string $name, string $slug, string $trigger): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'trigger_key' => $trigger,
            'trigger_config' => [],
            'conditions' => [],
            'actions' => [[
                'key' => 'audit',
                'type' => 'audit.record',
                'config' => ['event' => 'automation.tenant.test'],
            ]],
        ];
    }

    private function defaultOrganization(): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->where('is_default', true)->firstOrFail();
    }

    private function createOrganization(string $name, string $slug): EnterpriseOrganization
    {
        return EnterpriseOrganization::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'is_default' => false,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }

    private function addMember(EnterpriseOrganization $organization, User $user, string $role = 'member'): EnterpriseOrganizationMember
    {
        return EnterpriseOrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}
