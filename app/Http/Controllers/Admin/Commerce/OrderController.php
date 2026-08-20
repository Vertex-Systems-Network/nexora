<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CommerceCurrency;
use App\Models\CommerceCustomer;
use App\Models\CommerceOrder;
use App\Models\CommercePrice;
use App\Nexora\Commerce\Services\CommerceOrderService;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $orders=CommerceOrder::query()->with('customer:id,name,email')->withCount('items')->latest()->paginate(20)->withQueryString()->through(fn(CommerceOrder $o)=>[
            'id'=>$o->id,'number'=>$o->number,'status'=>$o->status,'customer'=>$o->customer?->name ?? 'Guest customer','email'=>$o->customer?->email,
            'items_count'=>$o->items_count,'currency'=>$o->currency,'total'=>$currencies->format((int)$o->total_minor,$o->currency),'paid'=>$currencies->format((int)$o->paid_minor,$o->currency),'created_at'=>$o->created_at?->toIso8601String(),
        ]);
        $prices=CommercePrice::query()->with('product:id,name,sku')->where('active',true)->latest()->limit(200)->get()->map(fn(CommercePrice $p)=>[
            'id'=>$p->id,'label'=>($p->product?->name??'Product').' — '.$currencies->format((int)$p->amount_minor,$p->currency).($p->billing_interval?' / '.$p->billing_interval:''),'currency'=>$p->currency,
        ])->values();
        return Inertia::render('Admin/Commerce/Orders',[
            'orders'=>$orders,'customers'=>CommerceCustomer::query()->orderBy('name')->limit(200)->get(['id','name','email']),
            'prices'=>$prices,'currencies'=>CommerceCurrency::query()->where('enabled',true)->orderByDesc('is_default')->orderBy('code')->get(['code','name']),
            'canManage'=>$request->user()?->hasPermission('commerce.orders.manage')??false,
        ]);
    }

    public function store(Request $request, CommerceOrderService $orders): RedirectResponse
    {
        $data=$request->validate(['customer_id'=>['nullable','uuid',new TenantExists('nx_commerce_customers')],'currency'=>['required','string','size:3'],'price_id'=>['required','uuid',TenantExists::through('nx_commerce_prices','nx_commerce_products','product_id')],'quantity'=>['required','integer','min:1','max:999']]);
        $customer=$data['customer_id']?CommerceCustomer::query()->findOrFail($data['customer_id']):null;
        $order=$orders->createDraft($customer,$data['currency'],[['price_id'=>$data['price_id'],'quantity'=>$data['quantity']]]);
        return back()->with('success','Draft order '.$order->number.' created.');
    }

    public function place(CommerceOrder $order, CommerceOrderService $orders): RedirectResponse
    {
        $orders->place($order);
        return back()->with('success','Order placed and is awaiting payment.');
    }

    public function invoice(CommerceOrder $order, InvoiceService $invoices): RedirectResponse
    {
        $invoice=$invoices->createFromOrder($order);
        return redirect()->route('admin.commerce.billing')->with('success','Invoice '.$invoice->number.' created.');
    }
}
