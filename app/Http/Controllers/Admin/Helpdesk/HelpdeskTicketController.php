<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Nexora\Enterprise\Validation\TenantMemberExists;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CrmContact;
use App\Models\HelpdeskTicket;
use App\Models\User;
use App\Nexora\Helpdesk\Contracts\HelpdeskTicketManagerContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class HelpdeskTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query=HelpdeskTicket::query()->with(['assignee:id,name','requesterUser:id,name,email','requesterContact:id,display_name,email','commerceCustomer:id,name,email'])->latest();
        $status=(string)$request->query('status',''); if($status!=='')$query->where('status',$status);
        $priority=(string)$request->query('priority',''); if($priority!=='')$query->where('priority',$priority);
        return Inertia::render('Admin/Helpdesk/Tickets',[
            'tickets'=>$query->paginate(25)->withQueryString()->through(fn(HelpdeskTicket $t)=>['id'=>$t->id,'reference'=>$t->reference,'subject'=>$t->subject,'status'=>$t->status,'priority'=>$t->priority,'requester'=>$t->requesterUser?->name??$t->requesterContact?->display_name??$t->commerceCustomer?->name??$t->requester_name??$t->requester_email??'Unknown requester','assignee'=>$t->assignee?->name,'first_response_due_at'=>$t->first_response_due_at?->toIso8601String(),'resolution_due_at'=>$t->resolution_due_at?->toIso8601String(),'first_response_breached'=>$t->first_response_breached,'resolution_breached'=>$t->resolution_breached,'created_at'=>$t->created_at?->toIso8601String()]),
            'filters'=>['status'=>$status,'priority'=>$priority],'users'=>$this->users(),'contacts'=>$this->contacts(),'customers'=>$this->customers(),'canManage'=>$request->user()?->hasPermission('helpdesk.tickets.manage')??false,
        ]);
    }

    public function store(Request $request, HelpdeskTicketManagerContract $tickets): RedirectResponse
    {
        $data=$request->validate(['requester_user_id'=>['nullable','integer',new TenantMemberExists()],'requester_contact_id'=>['nullable','uuid',new TenantExists('nx_crm_contacts')],'commerce_customer_id'=>['nullable','uuid',new TenantExists('nx_commerce_customers')],'requester_name'=>['nullable','string','max:180'],'requester_email'=>['nullable','email','max:255'],'subject'=>['required','string','max:240'],'priority'=>['required','in:low,normal,high,urgent'],'category'=>['nullable','string','max:120'],'assigned_to'=>['nullable','integer',new TenantMemberExists()],'message'=>['required','string','max:20000']]);
        $ticket=$tickets->create($data,$request->user()?->id);
        return redirect()->route('admin.helpdesk.tickets.show',$ticket)->with('success','Support ticket created.');
    }

    public function show(Request $request, HelpdeskTicket $ticket): Response
    {
        $ticket->load(['assignee:id,name,email','requesterUser:id,name,email','requesterContact:id,display_name,email','commerceCustomer:id,name,email','slaPolicy:id,name','messages.authorUser:id,name,email','events.actor:id,name']);
        return Inertia::render('Admin/Helpdesk/TicketShow',[
            'ticket'=>[
                'id'=>$ticket->id,'reference'=>$ticket->reference,'subject'=>$ticket->subject,'status'=>$ticket->status,'priority'=>$ticket->priority,'category'=>$ticket->category,'requester'=>$ticket->requesterUser?->name??$ticket->requesterContact?->display_name??$ticket->commerceCustomer?->name??$ticket->requester_name??$ticket->requester_email??'Unknown requester','requester_email'=>$ticket->requesterUser?->email??$ticket->requesterContact?->email??$ticket->commerceCustomer?->email??$ticket->requester_email,
                'assignee_id'=>$ticket->assigned_to ? (string)$ticket->assigned_to : '', 'assignee'=>$ticket->assignee?->name,'sla'=>$ticket->slaPolicy?->name,'first_response_due_at'=>$ticket->first_response_due_at?->toIso8601String(),'resolution_due_at'=>$ticket->resolution_due_at?->toIso8601String(),'first_responded_at'=>$ticket->first_responded_at?->toIso8601String(),'resolved_at'=>$ticket->resolved_at?->toIso8601String(),'first_response_breached'=>$ticket->first_response_breached,'resolution_breached'=>$ticket->resolution_breached,
                'messages'=>$ticket->messages->map(fn($m)=>['id'=>$m->id,'body'=>$m->body,'is_internal'=>$m->is_internal,'author'=>$m->authorUser?->name??$m->author_name??'External requester','created_at'=>$m->created_at?->toIso8601String()]),
                'events'=>$ticket->events->map(fn($e)=>['id'=>$e->id,'event_type'=>$e->event_type,'payload'=>$e->payload??[],'actor'=>$e->actor?->name,'occurred_at'=>$e->occurred_at?->toIso8601String()]),
            ],
            'users'=>$this->users(),'canManage'=>$request->user()?->hasPermission('helpdesk.tickets.manage')??false,
        ]);
    }

    public function message(Request $request, HelpdeskTicket $ticket, HelpdeskTicketManagerContract $tickets): RedirectResponse
    {
        $data=$request->validate(['body'=>['required','string','max:20000'],'is_internal'=>['required','boolean']]);
        try{$tickets->addMessage($ticket,$data['body'],$data['is_internal'],$request->user()?->id);}catch(InvalidArgumentException $e){throw ValidationException::withMessages(['body'=>$e->getMessage()]);}
        return back()->with('success',$data['is_internal']?'Internal note added.':'Reply added.');
    }

    public function state(Request $request, HelpdeskTicket $ticket, HelpdeskTicketManagerContract $tickets): RedirectResponse
    {
        $data=$request->validate(['status'=>['nullable','in:open,pending,resolved,closed'],'priority'=>['nullable','in:low,normal,high,urgent'],'assigned_to'=>['nullable','integer',new TenantMemberExists()]]);
        try{$tickets->updateState($ticket,$data,$request->user()?->id);}catch(InvalidArgumentException $e){throw ValidationException::withMessages(['status'=>$e->getMessage()]);}
        return back()->with('success','Ticket updated.');
    }

    private function users(): array { return User::query()->where('status','active')->orderBy('name')->get(['id','name'])->map(fn(User $u)=>['id'=>$u->id,'name'=>$u->name])->all(); }
    private function contacts(): array { return CrmContact::query()->orderBy('display_name')->get(['id','display_name','email'])->map(fn(CrmContact $c)=>['id'=>$c->id,'name'=>$c->display_name.($c->email?' · '.$c->email:'')])->all(); }
    private function customers(): array { return CommerceCustomer::query()->orderBy('name')->get(['id','name','email'])->map(fn(CommerceCustomer $c)=>['id'=>$c->id,'name'=>$c->name.' · '.$c->email])->all(); }
}
