<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskSlaPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HelpdeskSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Helpdesk/Settings',['policies'=>HelpdeskSlaPolicy::query()->orderByDesc('is_default')->orderBy('priority')->get()->map(fn(HelpdeskSlaPolicy $p)=>['id'=>$p->id,'name'=>$p->name,'priority'=>$p->priority,'first_response_minutes'=>$p->first_response_minutes,'resolution_minutes'=>$p->resolution_minutes,'active'=>$p->active,'is_default'=>$p->is_default])]);
    }

    public function sla(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'priority'=>['nullable','in:low,normal,high,urgent'],'first_response_minutes'=>['nullable','integer','min:1','max:525600'],'resolution_minutes'=>['nullable','integer','min:1','max:525600'],'active'=>['required','boolean'],'is_default'=>['required','boolean']]);
        if($data['is_default'])HelpdeskSlaPolicy::query()->update(['is_default'=>false]);
        HelpdeskSlaPolicy::query()->create($data+['business_hours'=>null]);
        return back()->with('success','SLA policy created.');
    }
}
