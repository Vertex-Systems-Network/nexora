<?php

declare(strict_types=1);

namespace App\Nexora\Helpdesk\Services;

use App\Models\HelpdeskSlaPolicy;
use App\Models\HelpdeskTicket;

final class HelpdeskSlaService
{
    public function policyFor(string $priority): ?HelpdeskSlaPolicy
    {
        return HelpdeskSlaPolicy::query()->where('active',true)->where(function($q) use($priority){$q->where('priority',$priority)->orWhereNull('priority');})
            ->orderByRaw('CASE WHEN priority IS NULL THEN 1 ELSE 0 END')->orderByDesc('is_default')->first();
    }

    public function deadlines(?HelpdeskSlaPolicy $policy, $from = null): array
    {
        $from=$from?now()->parse($from):now();
        return [
            'first_response_due_at'=>$policy?->first_response_minutes ? $from->copy()->addMinutes((int)$policy->first_response_minutes) : null,
            'resolution_due_at'=>$policy?->resolution_minutes ? $from->copy()->addMinutes((int)$policy->resolution_minutes) : null,
        ];
    }

    public function refreshBreaches(HelpdeskTicket $ticket): HelpdeskTicket
    {
        $changed=false;$now=now();
        if (! $ticket->first_responded_at && $ticket->first_response_due_at && $ticket->first_response_due_at->lt($now) && ! $ticket->first_response_breached) { $ticket->first_response_breached=true;$changed=true; }
        if (! in_array($ticket->status,['resolved','closed'],true) && $ticket->resolution_due_at && $ticket->resolution_due_at->lt($now) && ! $ticket->resolution_breached) { $ticket->resolution_breached=true;$changed=true; }
        if ($changed) $ticket->save();
        return $ticket;
    }
}
