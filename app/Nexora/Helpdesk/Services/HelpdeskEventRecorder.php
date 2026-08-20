<?php

declare(strict_types=1);

namespace App\Nexora\Helpdesk\Services;

use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketEvent;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;

final readonly class HelpdeskEventRecorder
{
    public function __construct(private AutomationEventBusContract $automation) {}
    public function record(HelpdeskTicket $ticket,string $eventType,array $payload=[],?int $actorId=null): HelpdeskTicketEvent
    {
        $event=HelpdeskTicketEvent::query()->create(['ticket_id'=>$ticket->id,'event_type'=>$eventType,'payload'=>$payload,'actor_id'=>$actorId,'occurred_at'=>now()]);
        $this->automation->emit('helpdesk.'.$eventType,['ticket'=>['id'=>$ticket->id,'reference'=>$ticket->reference,'subject'=>$ticket->subject,'status'=>$ticket->status,'priority'=>$ticket->priority,'assigned_to'=>$ticket->assigned_to],'event'=>$payload],'helpdesk.ticket',$ticket->id);
        return $event;
    }
}
