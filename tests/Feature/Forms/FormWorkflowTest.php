<?php

declare(strict_types=1);

namespace Tests\Feature\Forms;

use App\Jobs\ExecuteWorkflowRunJob;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_create_form_and_open_management_surfaces(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post('/admin/forms', $this->formPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $form = FormDefinition::query()->where('slug', 'contact-form')->firstOrFail();
        self::assertSame('active', $form->status);
        self::assertSame('email', data_get($form->fields, '0.type'));
        self::assertSame('department', data_get($form->fields, '2.key'));
        self::assertNotNull($form->tenant_id);

        $this->actingAs($admin)->get('/admin/forms')->assertOk();
        $this->actingAs($admin)->get('/admin/forms/contact-form/edit')->assertOk();
        $this->actingAs($admin)->get('/admin/forms/contact-form/submissions')->assertOk();
    }

    public function test_public_submission_is_validated_stored_and_emits_automation_event(): void
    {
        Queue::fake();
        $admin = $this->administrator();
        $this->actingAs($admin)->post('/admin/forms', $this->formPayload())->assertSessionHasNoErrors();
        $form = FormDefinition::query()->where('slug', 'contact-form')->firstOrFail();

        $workflow = Workflow::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Contact form notification',
            'slug' => 'contact-form-notification',
            'status' => 'active',
            'trigger_key' => 'form.submitted',
            'trigger_config' => [],
            'conditions' => [[
                'field' => 'form.slug',
                'operator' => 'equals',
                'value' => 'contact-form',
            ]],
            'actions' => [[
                'key' => 'step-1',
                'type' => 'audit.record',
                'config' => ['event' => 'forms.contact.received'],
            ]],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->post('/logout');
        $this->get('/forms/contact-form')
            ->assertOk()
            ->assertSee('Contact form')
            ->assertSee('name="email"', false)
            ->assertSee('name="department"', false)
            ->assertSee('content="noindex,follow"', false);

        $this->post('/forms/contact-form', [
            'email' => 'not-an-email',
            'message' => 'Please contact me.',
            'department' => 'sales',
        ])->assertSessionHasErrors(['email']);
        self::assertSame(0, FormSubmission::query()->count());

        $this->post('/forms/contact-form', [
            'email' => 'customer@example.test',
            'message' => 'Please contact me about Nexora.',
            'department' => 'sales',
            'unexpected_admin_flag' => 'must-not-persist',
        ])->assertSessionHasNoErrors()->assertRedirect('/forms/contact-form');

        $submission = FormSubmission::query()->firstOrFail();
        self::assertSame('customer@example.test', $submission->values['email'] ?? null);
        self::assertSame('sales', $submission->values['department'] ?? null);
        self::assertArrayNotHasKey('unexpected_admin_flag', $submission->values);
        self::assertSame($form->tenant_id, $submission->tenant_id);
        self::assertSame(1, $form->fresh()->submission_count);

        $this->assertDatabaseHas('nx_automation_events', [
            'event_key' => 'form.submitted',
            'source_type' => 'form',
            'source_id' => (string) $form->id,
        ]);
        $this->assertDatabaseHas('nx_workflow_runs', [
            'workflow_id' => $workflow->id,
            'status' => 'queued',
        ]);
        Queue::assertPushed(ExecuteWorkflowRunJob::class);
    }

    public function test_auth_required_form_rejects_guest_and_honeypot_does_not_store_data(): void
    {
        $admin = $this->administrator();
        $payload = $this->formPayload();
        $payload['settings']['require_auth'] = true;
        $this->actingAs($admin)->post('/admin/forms', $payload)->assertSessionHasNoErrors();
        $this->post('/logout');

        $this->post('/forms/contact-form', [
            'email' => 'guest@example.test',
            'message' => 'Guest response.',
            'department' => 'support',
        ])->assertSessionHasErrors(['form']);
        self::assertSame(0, FormSubmission::query()->count());

        $form = FormDefinition::query()->where('slug', 'contact-form')->firstOrFail();
        $form->forceFill([
            'settings' => array_merge((array) $form->settings, ['require_auth' => false]),
        ])->save();

        $this->post('/forms/contact-form', [
            '_nx_website' => 'https://spam.example',
            'email' => 'spam@example.test',
            'message' => 'Automated spam.',
            'department' => 'sales',
        ])->assertSessionHasNoErrors()->assertRedirect('/forms/contact-form');
        self::assertSame(0, FormSubmission::query()->count());
    }

    public function test_paused_form_is_not_publicly_available(): void
    {
        $admin = $this->administrator();
        $payload = $this->formPayload();
        $payload['status'] = 'paused';
        $this->actingAs($admin)->post('/admin/forms', $payload)->assertSessionHasNoErrors();
        $this->post('/logout');

        $this->get('/forms/contact-form')->assertNotFound();
        $this->post('/forms/contact-form', [
            'email' => 'customer@example.test',
            'message' => 'Should not be accepted.',
            'department' => 'sales',
        ])->assertNotFound();
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        return $admin;
    }

    /** @return array<string,mixed> */
    private function formPayload(): array
    {
        return [
            'name' => 'Contact form',
            'slug' => 'contact-form',
            'description' => 'Send the Nexora team a message.',
            'status' => 'active',
            'fields' => [
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'placeholder' => 'you@example.com',
                    'help' => '',
                    'max_length' => 255,
                    'options' => [],
                ],
                [
                    'key' => 'message',
                    'label' => 'Message',
                    'type' => 'textarea',
                    'required' => true,
                    'placeholder' => '',
                    'help' => '',
                    'max_length' => 4000,
                    'options' => [],
                ],
                [
                    'key' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => '',
                    'help' => '',
                    'max_length' => 255,
                    'options' => [
                        ['value' => 'sales', 'label' => 'Sales'],
                        ['value' => 'support', 'label' => 'Support'],
                    ],
                ],
            ],
            'settings' => [
                'success_message' => 'Thanks. We received your message.',
                'submit_button' => 'Send message',
                'require_auth' => false,
                'indexable' => false,
            ],
        ];
    }
}
