<?php

declare(strict_types=1);

namespace App\Nexora\Helpdesk\Services;

use App\Models\HelpdeskMessage;
use App\Models\HelpdeskTicket;
use App\Nexora\Helpdesk\Contracts\HelpdeskTicketManagerContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class HelpdeskTicketManager implements HelpdeskTicketManagerContract
{
    public function __construct(private HelpdeskSlaService $sla, private HelpdeskEventRecorder $events) {}

    public function create(array $attributes, ?int $actorId = null): HelpdeskTicket
    {
        return DB::transaction(function () use($attributes,$actorId): HelpdeskTicket {
            $priority=(string)($attributes['priority']??'normal');
            $policy=isset($attributes['sla_policy_id']) ? \App\Models\HelpdeskSlaPolicy::query()->find($attributes['sla_policy_id']) : $this->sla->policyFor($priority);
            $deadlines=$this->sla->deadlines($policy);
            $ticket=HelpdeskTicket::query()->create([
                'reference'=>$this->nextReference(),'requester_user_id'=>$attributes['requester_user_id']??null,'requester_contact_id'=>$attributes['requester_contact_id']??null,'commerce_customer_id'=>$attributes['commerce_customer_id']??null,
                'requester_name'=>$attributes['requester_name']??null,'requester_email'=>$attributes['requester_email']??null,'subject'=>$attributes['subject'],'status'=>$attributes['status']??'open','priority'=>$priority,'category'=>$attributes['category']??null,
                'assigned_to'=>$attributes['assigned_to']??null,'sla_policy_id'=>$policy?->id,'first_response_due_at'=>$deadlines['first_response_due_at'],'resolution_due_at'=>$deadlines['resolution_due_at'],'metadata'=>$attributes['metadata']??[],
            ]);
            if (($attributes['message']??'')!=='') HelpdeskMessage::query()->create(['ticket_id'=>$ticket->id,'author_user_id'=>$attributes['requester_user_id']??null,'author_name'=>$attributes['requester_name']??null,'author_email'=>$attributes['requester_email']??null,'body'=>$attributes['message'],'is_internal'=>false]);
            $this->events->record($ticket,'ticket.created',['sla_policy_id'=>$policy?->id],$actorId);
            return $ticket;
        });
    }

    public function addMessage(HelpdeskTicket $ticket, string $body, bool $internal, ?int $actorId = null): HelpdeskTicket
    {
        if (trim($body)==='') throw new InvalidArgumentException('Message cannot be empty.');
        return DB::transaction(function() use($ticket,$body,$internal,$actorId): HelpdeskTicket {
            $locked=HelpdeskTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            HelpdeskMessage::query()->create(['ticket_id'=>$locked->id,'author_user_id'=>$actorId,'body'=>trim($body),'is_internal'=>$internal]);
            if (! $internal && $actorId!==null && ! $locked->first_responded_at) { $locked->first_responded_at=now(); if ($locked->first_response_due_at && $locked->first_responded_at->gt($locked->first_response_due_at)) $locked->first_response_breached=true; }
            if (! $internal && in_array($locked->status,['resolved','closed'],true)) { $locked->status='open';$locked->resolved_at=null;$locked->closed_at=null; }
            $locked->save();
            $this->events->record($locked,$internal?'note.added':'reply.added',['internal'=>$internal],$actorId);
            return $locked->refresh();
        });
    }

    public function updateState(HelpdeskTicket $ticket, array $attributes, ?int $actorId = null): HelpdeskTicket
    {
        return DB::transaction(function() use($ticket,$attributes,$actorId): HelpdeskTicket {
            $locked=HelpdeskTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            $before=['status'=>$locked->status,'priority'=>$locked->priority,'assigned_to'=>$locked->assigned_to];
            if (isset($attributes['status'])) {
                if (! in_array($attributes['status'],['open','pending','resolved','closed'],true)) throw new InvalidArgumentException('Unsupported ticket status.');
                $locked->status=$attributes['status'];
                if ($locked->status==='resolved' && ! $locked->resolved_at) { $locked->resolved_at=now(); if ($locked->resolution_due_at && $locked->resolved_at->gt($locked->resolution_due_at)) $locked->resolution_breached=true; }
                if ($locked->status==='closed' && ! $locked->closed_at) { $locked->closed_at=now();$locked->resolved_at??=now(); if ($locked->resolution_due_at && $locked->resolved_at->gt($locked->resolution_due_at)) $locked->resolution_breached=true; }
                if (in_array($locked->status,['open','pending'],true)) { $locked->resolved_at=null;$locked->closed_at=null; }
            }
            if (array_key_exists('assigned_to',$attributes)) $locked->assigned_to=$attributes['assigned_to']?:null;
            if (isset($attributes['priority']) && in_array($attributes['priority'],['low','normal','high','urgent'],true)) {
                if ($locked->priority!==$attributes['priority']) {
                    $locked->priority=$attributes['priority'];$policy=$this->sla->policyFor($locked->priority);$deadlines=$this->sla->deadlines($policy,$locked->created_at);
                    $locked->sla_policy_id=$policy?->id;$locked->first_response_due_at=$deadlines['first_response_due_at'];$locked->resolution_due_at=$deadlines['resolution_due_at'];
                }
            }
            $locked->save();
            $after=['status'=>$locked->status,'priority'=>$locked->priority,'assigned_to'=>$locked->assigned_to];
            foreach ($after as $key=>$value) if ($before[$key]!==$value) $this->events->record($locked,$key.'.changed',['from'=>$before[$key],'to'=>$value],$actorId);
            return $this->sla->refreshBreaches($locked->refresh());
        });
    }

    private function nextReference(): string
    {
        for($i=0;$i<8;$i++) { $ref='NX-'.now()->format('ymd').'-'.strtoupper(Str::random(6)); if (! HelpdeskTicket::query()->where('reference',$ref)->exists()) return $ref; }
        return 'NX-'.strtoupper((string)Str::ulid());
    }
}
