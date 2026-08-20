<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceInvoice;
use App\Models\CommercePaymentTransaction;
use App\Models\CommerceRefund;
use App\Models\CommerceSubscription;
use App\Nexora\Commerce\Services\CurrencyManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $invoices=CommerceInvoice::query()->with('customer:id,name,email')->latest()->paginate(20,['*'],'invoices_page')->withQueryString()->through(fn(CommerceInvoice $i)=>[
            'id'=>$i->id,'number'=>$i->number,'status'=>$i->status,'customer'=>$i->customer?->name??'Guest customer','total'=>$currencies->format((int)$i->total_minor,$i->currency),'due'=>$currencies->format((int)$i->amount_due_minor,$i->currency),'issued_at'=>$i->issued_at?->toIso8601String(),'due_at'=>$i->due_at?->toIso8601String(),
        ]);
        $transactions=CommercePaymentTransaction::query()->latest()->limit(25)->get()->map(fn(CommercePaymentTransaction $t)=>[
            'id'=>$t->id,'provider'=>$t->provider_key,'type'=>$t->type,'status'=>$t->status,'amount'=>$currencies->format((int)$t->amount_minor,$t->currency),'reference'=>$t->provider_reference,'processed_at'=>$t->processed_at?->toIso8601String(),
        ]);
        $refunds=CommerceRefund::query()->latest()->limit(25)->get()->map(fn(CommerceRefund $r)=>['id'=>$r->id,'provider'=>$r->provider_key,'status'=>$r->status,'amount'=>$currencies->format((int)$r->amount_minor,$r->currency),'reason'=>$r->reason,'created_at'=>$r->created_at?->toIso8601String()]);
        $subscriptions=CommerceSubscription::query()->with(['customer:id,name','product:id,name'])->latest()->limit(25)->get()->map(fn(CommerceSubscription $s)=>['id'=>$s->id,'customer'=>$s->customer?->name,'product'=>$s->product?->name,'provider'=>$s->provider_key,'status'=>$s->status,'amount'=>$currencies->format((int)$s->amount_minor,$s->currency),'interval'=>$s->billing_interval,'period_end'=>$s->current_period_end?->toIso8601String()]);
        return Inertia::render('Admin/Commerce/Billing',['invoices'=>$invoices,'transactions'=>$transactions,'refunds'=>$refunds,'subscriptions'=>$subscriptions]);
    }
}
