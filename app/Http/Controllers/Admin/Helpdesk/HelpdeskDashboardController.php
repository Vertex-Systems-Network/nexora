<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskTicket;
use App\Nexora\Helpdesk\Services\HelpdeskSlaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HelpdeskDashboardController extends Controller
{
    public function __invoke(Request $request, HelpdeskSlaService $sla): Response
    {
        HelpdeskTicket::query()->whereIn('status',['open','pending'])->latest()->limit(100)->get()->each(fn(HelpdeskTicket $ticket)=>$sla->refreshBreaches($ticket));
        return Inertia::render('Admin/Helpdesk/Index',[
            'summary'=>[
                'open'=>HelpdeskTicket::query()->where('status','open')->count(),
                'pending'=>HelpdeskTicket::query()->where('status','pending')->count(),
                'urgent'=>HelpdeskTicket::query()->whereIn('status',['open','pending'])->where('priority','urgent')->count(),
                'unassigned'=>HelpdeskTicket::query()->whereIn('status',['open','pending'])->whereNull('assigned_to')->count(),
                'response_breaches'=>HelpdeskTicket::query()->where('first_response_breached',true)->whereNull('first_responded_at')->count(),
                'resolution_breaches'=>HelpdeskTicket::query()->where('resolution_breached',true)->whereNotIn('status',['resolved','closed'])->count(),
            ],
            'recent'=>HelpdeskTicket::query()->with('assignee:id,name')->latest()->limit(10)->get()->map(fn(HelpdeskTicket $t)=>['id'=>$t->id,'reference'=>$t->reference,'subject'=>$t->subject,'status'=>$t->status,'priority'=>$t->priority,'assignee'=>$t->assignee?->name,'created_at'=>$t->created_at?->toIso8601String()]),
        ]);
    }
}
