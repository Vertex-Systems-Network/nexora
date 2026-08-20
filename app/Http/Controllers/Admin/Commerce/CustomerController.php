<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers=CommerceCustomer::query()->withCount(['orders','subscriptions'])->latest()->paginate(20)->withQueryString()->through(fn(CommerceCustomer $c)=>[
            'id'=>$c->id,'name'=>$c->name,'email'=>$c->email,'phone'=>$c->phone,'orders_count'=>$c->orders_count,'subscriptions_count'=>$c->subscriptions_count,'created_at'=>$c->created_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Commerce/Customers',['customers'=>$customers,'canManage'=>$request->user()?->hasPermission('commerce.customers.manage')??false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:200'],'email'=>['required','email','max:255'],'phone'=>['nullable','string','max:80'],'tax_id'=>['nullable','string','max:160']]);
        CommerceCustomer::query()->create($data);
        return back()->with('success','Customer created.');
    }
}
