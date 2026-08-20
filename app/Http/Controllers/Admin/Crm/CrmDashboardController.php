<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmOrganization;
use App\Nexora\Commerce\Services\CurrencyManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CrmDashboardController extends Controller
{
    public function __invoke(Request $request, CurrencyManager $currencies): Response
    {
        $currency=$currencies->defaultCode();
        $openValue=(int)CrmOpportunity::query()->where('status','open')->where('currency',$currency)->sum('amount_minor');
        return Inertia::render('Admin/Crm/Index',[
            'summary'=>[
                'organizations'=>CrmOrganization::query()->count(),
                'contacts'=>CrmContact::query()->count(),
                'open_leads'=>CrmLead::query()->whereNotIn('status',['converted','disqualified'])->count(),
                'open_opportunities'=>CrmOpportunity::query()->where('status','open')->count(),
                'won_opportunities'=>CrmOpportunity::query()->where('status','won')->count(),
                'pipeline_value'=>$currencies->format($openValue,$currency),
                'currency'=>$currency,
            ],
            'recentOpportunities'=>CrmOpportunity::query()->with(['organization:id,name','contact:id,display_name','stage:id,name'])->latest()->limit(8)->get()->map(fn(CrmOpportunity $o)=>[
                'id'=>$o->id,'name'=>$o->name,'status'=>$o->status,'stage'=>$o->stage?->name,'organization'=>$o->organization?->name,'contact'=>$o->contact?->display_name,
                'amount'=>$o->currency?$currencies->format((int)$o->amount_minor,$o->currency):'—','updated_at'=>$o->updated_at?->toIso8601String(),
            ]),
            'recentActivities'=>CrmActivity::query()->latest('occurred_at')->limit(8)->get()->map(fn(CrmActivity $a)=>[
                'id'=>$a->id,'type'=>$a->type,'title'=>$a->title,'subject_type'=>$a->subject_type,'subject_id'=>$a->subject_id,'occurred_at'=>$a->occurred_at?->toIso8601String(),
            ]),
        ]);
    }
}
