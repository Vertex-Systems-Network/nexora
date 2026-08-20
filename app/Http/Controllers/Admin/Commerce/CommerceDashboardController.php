<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use App\Models\CommercePaymentTransaction;
use App\Models\CommerceProduct;
use App\Models\CommerceSubscription;
use App\Nexora\Commerce\Services\CurrencyManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CommerceDashboardController extends Controller
{
    public function __invoke(Request $request, CurrencyManager $currencies): Response
    {
        $currency=$currencies->defaultCode();
        $revenue=(int)CommercePaymentTransaction::query()->where('currency',$currency)->whereIn('status',['succeeded','captured','paid'])->whereIn('type',['payment','capture'])->sum('amount_minor');
        $refunded=(int)\App\Models\CommerceRefund::query()->where('currency',$currency)->whereIn('status',['succeeded','refunded'])->sum('amount_minor');
        return Inertia::render('Admin/Commerce/Index',[
            'summary'=>[
                'products'=>CommerceProduct::query()->where('status','active')->count(),
                'customers'=>CommerceCustomer::query()->count(),
                'orders'=>CommerceOrder::query()->count(),
                'open_invoices'=>CommerceInvoice::query()->whereIn('status',['open','overdue'])->count(),
                'active_subscriptions'=>CommerceSubscription::query()->whereIn('status',['active','trialing'])->count(),
                'revenue'=>$currencies->format(max(0,$revenue-$refunded),$currency),
                'currency'=>$currency,
            ],
            'recentOrders'=>CommerceOrder::query()->with('customer:id,name,email')->latest()->limit(8)->get()->map(fn(CommerceOrder $o)=>[
                'id'=>$o->id,'number'=>$o->number,'status'=>$o->status,'customer'=>$o->customer?->name ?? 'Guest customer','currency'=>$o->currency,
                'total'=>$currencies->format((int)$o->total_minor,$o->currency),'created_at'=>$o->created_at?->toIso8601String(),
            ]),
            'providers'=>app(\App\Nexora\Commerce\Services\PaymentProviderRegistry::class)->all() === [] ? [] : collect(app(\App\Nexora\Commerce\Services\PaymentProviderRegistry::class)->all())->map(fn($p)=>['key'=>$p->key(),'label'=>$p->label(),'capabilities'=>$p->capabilities()])->values(),
        ]);
    }
}
