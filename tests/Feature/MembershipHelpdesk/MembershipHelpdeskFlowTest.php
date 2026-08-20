<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipHelpdesk;

use App\Models\Document;
use App\Models\HelpdeskSlaPolicy;
use App\Models\HelpdeskTicket;
use App\Models\MembershipAccessPolicy;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Helpdesk\Contracts\HelpdeskTicketManagerContract;
use App\Nexora\Membership\Contracts\MembershipAccessContract;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MembershipHelpdeskFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_open_membership_and_helpdesk_workspaces(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->roles()->attach(Role::query()->where('slug','administrator')->value('id'));
        foreach (['/admin/membership','/admin/membership/plans','/admin/membership/members','/admin/membership/access-policies','/admin/helpdesk','/admin/helpdesk/tickets','/admin/helpdesk/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_document_policy_grants_access_from_plan_entitlement(): void
    {
        $user=User::factory()->create();
        $plan=MembershipPlan::query()->create(['name'=>'Premium','slug'=>'premium','status'=>'active','metadata'=>[]]);
        MembershipEntitlement::query()->create(['plan_id'=>$plan->id,'key'=>'content.premium','label'=>'Premium content','value_type'=>'boolean','value'=>true,'active'=>true]);
        $document=Document::query()->create(['uuid'=>(string)\Illuminate\Support\Str::uuid(),'type'=>'document','status'=>'published','title'=>'Protected guide','slug'=>'protected-guide','content'=>['version'=>1,'blocks'=>[]],'schema_version'=>1,'published_at'=>now()]);
        MembershipAccessPolicy::query()->create(['name'=>'Premium gate','resource_type'=>'document','resource_id'=>(string)$document->id,'evaluation'=>'all','required_plan_ids'=>[],'required_entitlements'=>['content.premium'],'active'=>true]);
        $access=app(MembershipAccessContract::class);
        self::assertFalse($access->canAccess($user,'document',(string)$document->id));
        app(MembershipManagerContract::class)->grant($plan,$user,['status'=>'active']);
        self::assertTrue($access->canAccess($user,'document',(string)$document->id));
    }

    public function test_ticket_creation_applies_sla_and_records_conversation(): void
    {
        $admin=User::factory()->create();
        HelpdeskSlaPolicy::query()->update(['is_default'=>false]);
        $sla=HelpdeskSlaPolicy::query()->create(['name'=>'Test SLA','priority'=>'urgent','first_response_minutes'=>30,'resolution_minutes'=>120,'active'=>true,'is_default'=>true]);
        $ticket=app(HelpdeskTicketManagerContract::class)->create(['requester_user_id'=>$admin->id,'subject'=>'Production issue','priority'=>'urgent','message'=>'Please help.'],$admin->id);
        self::assertSame($sla->id,$ticket->sla_policy_id);
        self::assertNotNull($ticket->first_response_due_at);
        self::assertDatabaseHas('nx_helpdesk_messages',['ticket_id'=>$ticket->id,'body'=>'Please help.','is_internal'=>false]);
        self::assertDatabaseHas('nx_helpdesk_ticket_events',['ticket_id'=>$ticket->id,'event_type'=>'ticket.created']);
    }
}
