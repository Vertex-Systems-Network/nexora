<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ExecuteWorkflowRunJob;
use App\Models\Role;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WebhookReceipt;
use App\Models\Workflow;
use App\Nexora\Automation\Services\WebhookSigner;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AutomationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_create_and_queue_manual_workflow(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));

        $this->actingAs($admin)->post('/admin/automation', [
            'name'=>'Notify operations','slug'=>'notify-operations','description'=>'Manual notification test','status'=>'active','trigger_key'=>'manual','trigger_config'=>[],
            'conditions'=>[],'actions'=>[['key'=>'step-1','type'=>'admin.notification','config'=>['user_id'=>$admin->id,'title'=>'Automation test','message'=>'Manual workflow ran.']]],
        ])->assertSessionHasNoErrors();

        $workflow = Workflow::query()->where('slug','notify-operations')->firstOrFail();
        $this->actingAs($admin)->post('/admin/automation/'.$workflow->id.'/run')->assertRedirect();
        $this->assertDatabaseHas('nx_workflow_runs',['workflow_id'=>$workflow->id,'status'=>'queued']);
        Queue::assertPushed(ExecuteWorkflowRunJob::class);
    }

    public function test_verified_inbound_webhook_is_idempotent_and_queues_matching_workflow(): void
    {
        Queue::fake();
        $secret = 'test-webhook-secret';
        $endpoint = WebhookEndpoint::query()->create(['uuid'=>(string)Str::uuid(),'name'=>'CRM','slug'=>'crm','secret'=>$secret,'enabled'=>true,'allowed_ips'=>[]]);
        $admin = User::factory()->create(['email_verified_at'=>now()]);
        Workflow::query()->create([
            'uuid'=>(string)Str::uuid(),'name'=>'CRM webhook','slug'=>'crm-webhook','status'=>'active','trigger_key'=>'webhook.inbound','trigger_config'=>['endpoint_id'=>$endpoint->id],
            'conditions'=>[],'actions'=>[['key'=>'step-1','type'=>'admin.notification','config'=>['user_id'=>$admin->id,'title'=>'CRM event','message'=>'Received webhook.']]],
        ]);
        $payload=['contact'=>['id'=>123,'email'=>'person@example.com']];
        $body=json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp=(string) now()->timestamp;
        $signature=app(WebhookSigner::class)->signature($secret,$timestamp,$body);
        $headers=['X-Nexora-Timestamp'=>$timestamp,'X-Nexora-Signature'=>$signature,'Idempotency-Key'=>'crm-contact-123','Content-Type'=>'application/json'];

        $this->withHeaders($headers)->postJson('/hooks/'.$endpoint->uuid,$payload)->assertAccepted();
        $this->withHeaders($headers)->postJson('/hooks/'.$endpoint->uuid,$payload)->assertOk()->assertJson(['duplicate'=>true]);
        self::assertSame(1, WebhookReceipt::query()->where('webhook_endpoint_id',$endpoint->id)->count());
        Queue::assertPushed(ExecuteWorkflowRunJob::class, 1);
    }

    public function test_invalid_inbound_signature_is_rejected(): void
    {
        $endpoint = WebhookEndpoint::query()->create(['uuid'=>(string)Str::uuid(),'name'=>'Secure','slug'=>'secure','secret'=>'correct-secret','enabled'=>true,'allowed_ips'=>[]]);
        $this->withHeaders(['X-Nexora-Timestamp'=>(string)now()->timestamp,'X-Nexora-Signature'=>'v1=invalid'])->postJson('/hooks/'.$endpoint->uuid,['hello'=>'world'])->assertUnauthorized();
        self::assertSame(0, WebhookReceipt::query()->count());
    }
}
