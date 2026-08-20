<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CommerceCustomer;
use App\Models\CrmContact;
use App\Models\CrmOrganization;
use App\Nexora\Crm\Contracts\CrmCommerceLinkContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

final class CommerceLinkController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Crm/CommerceLinks',[
            'customers'=>CommerceCustomer::query()->orderBy('name')->get(['id','name','email']),
            'contacts'=>CrmContact::query()->orderBy('display_name')->get(['id','display_name','organization_id']),
            'organizations'=>CrmOrganization::query()->orderBy('name')->get(['id','name']),
            'links'=>\App\Models\CrmCommerceLink::query()->with(['customer:id,name,email','contact:id,display_name','organization:id,name','linkedBy:id,name'])->latest('linked_at')->paginate(20)->withQueryString()->through(fn($l)=>['id'=>$l->id,'customer'=>$l->customer?->name,'email'=>$l->customer?->email,'contact'=>$l->contact?->display_name,'organization'=>$l->organization?->name,'linked_by'=>$l->linkedBy?->name,'linked_at'=>$l->linked_at?->toIso8601String()]),
        ]);
    }

    public function store(Request $request, CrmCommerceLinkContract $links): RedirectResponse
    {
        $data=$request->validate(['commerce_customer_id'=>['required','uuid',new TenantExists('nx_commerce_customers')],'contact_id'=>['nullable','uuid',new TenantExists('nx_crm_contacts')],'organization_id'=>['nullable','uuid',new TenantExists('nx_crm_organizations')]]);
        try { $links->link(CommerceCustomer::query()->findOrFail($data['commerce_customer_id']),isset($data['contact_id'])?CrmContact::query()->findOrFail($data['contact_id']):null,isset($data['organization_id'])?CrmOrganization::query()->findOrFail($data['organization_id']):null,$request->user()?->id); } catch (InvalidArgumentException $exception) { throw ValidationException::withMessages(['commerce_customer_id'=>$exception->getMessage()]); }
        return back()->with('success','Commerce customer linked to CRM.');
    }
}
